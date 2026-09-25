<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TenantSubscription Model
 *
 * Represents an active or historical SaaS subscription for an instructor (Tenant).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $plan_id
 * @property string $status (active, past_due, suspended, canceled)
 * @property Carbon $current_period_start
 * @property Carbon $current_period_end
 * @property Carbon|null $grace_period_ends_at
 * @property Carbon|null $extended_until
 * @property float $price
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TenantSubscription extends Model
{
    use HasFactory;

    protected $table = 'tenant_subscriptions';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'grace_period_ends_at',
        'extended_until',
        'price',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'plan_id' => 'integer',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'extended_until' => 'datetime',
            'price' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected $appends = [
        'is_past_due',
        'is_in_grace_period',
        'is_manually_extended',
        'is_read_only',
    ];

    public function getIsPastDueAttribute(): bool
    {
        return $this->isPastDue();
    }

    public function getIsInGracePeriodAttribute(): bool
    {
        return $this->isInGracePeriod();
    }

    public function getIsManuallyExtendedAttribute(): bool
    {
        return $this->isManuallyExtended();
    }

    public function getIsReadOnlyAttribute(): bool
    {
        return $this->isReadOnly();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'tenant_subscription_id');
    }

    /**
     * Determine the effective deadline for grace period or manual extension.
     */
    public function effectiveGraceDeadline(): ?Carbon
    {
        if ($this->extended_until !== null) {
            return $this->extended_until;
        }

        if ($this->grace_period_ends_at !== null) {
            return $this->grace_period_ends_at;
        }

        $graceDays = (int) config('billing.grace_period_days', 5);

        return $this->current_period_end->copy()->addDays($graceDays);
    }

    /**
     * Check if subscription has passed its renewal date.
     */
    public function isPastDue(): bool
    {
        if ($this->status === 'canceled') {
            return true;
        }

        return now()->greaterThan($this->current_period_end);
    }

    /**
     * Check if subscription is within the allowable grace period (or manual extension).
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->isPastDue()) {
            return false;
        }

        $deadline = $this->effectiveGraceDeadline();

        if ($deadline === null) {
            return false;
        }

        return now()->lessThanOrEqualTo($deadline);
    }

    /**
     * Check if subscription has been manually extended by admin.
     */
    public function isManuallyExtended(): bool
    {
        if ($this->extended_until === null) {
            return false;
        }

        return now()->lessThanOrEqualTo($this->extended_until);
    }

    /**
     * Check if subscription is in read-only mode (overdue and grace period expired).
     */
    public function isReadOnly(): bool
    {
        if ($this->status === 'canceled') {
            return true;
        }

        if (!$this->isPastDue()) {
            return false;
        }

        return !$this->isInGracePeriod();
    }

    /**
     * Check if the tenant can perform data mutations (create, update, delete).
     */
    public function canPerformMutations(): bool
    {
        return !$this->isReadOnly();
    }

    /**
     * Calculate days remaining in grace period.
     */
    public function daysLeftInGrace(): int
    {
        $deadline = $this->effectiveGraceDeadline();

        if ($deadline === null || now()->greaterThan($deadline)) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($deadline, false) / 86400);
    }

    /**
     * Compile banner display details for the instructor frontend.
     *
     * @return array{show_banner: bool, type: string|null, message: string|null, deadline: string|null, days_left: int, is_read_only: bool}
     */
    public function bannerData(): array
    {
        if (!$this->isPastDue()) {
            return [
                'show_banner' => false,
                'type' => null,
                'message' => null,
                'deadline' => null,
                'days_left' => 0,
                'is_read_only' => false,
            ];
        }

        $deadline = $this->effectiveGraceDeadline();
        $formattedDeadline = $deadline ? $deadline->format('d/m/Y') : '';
        $daysLeft = $this->daysLeftInGrace();

        if ($this->isReadOnly()) {
            $msg = config('billing.messages.suspended', 'Seu plano está suspenso por falta de pagamento. A conta está em modo somente leitura até a regularização.');

            return [
                'show_banner' => true,
                'type' => 'danger',
                'message' => $msg,
                'deadline' => $formattedDeadline,
                'days_left' => 0,
                'is_read_only' => true,
            ];
        }

        if ($this->isManuallyExtended()) {
            $template = config('billing.messages.extended', 'O prazo do seu plano foi prorrogado pelo administrador até :deadline. Regularize seu pagamento.');
            $msg = str_replace(':deadline', $formattedDeadline, $template);

            return [
                'show_banner' => true,
                'type' => 'warning',
                'message' => $msg,
                'deadline' => $formattedDeadline,
                'days_left' => $daysLeft,
                'is_read_only' => false,
            ];
        }

        $template = config('billing.messages.grace_period', 'Identificamos uma pendência no pagamento do seu plano. Regularize seus dados de pagamento até :deadline para evitar a suspensão dos serviços.');
        $msg = str_replace(':deadline', $formattedDeadline, $template);

        return [
            'show_banner' => true,
            'type' => 'warning',
            'message' => $msg,
            'deadline' => $formattedDeadline,
            'days_left' => $daysLeft,
            'is_read_only' => false,
        ];
    }
}
