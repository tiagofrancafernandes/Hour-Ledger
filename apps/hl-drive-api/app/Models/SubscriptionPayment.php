<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubscriptionPayment Model
 *
 * Represents an invoice/payment record for an instructor's SaaS subscription.
 *
 * @property int $id
 * @property int $tenant_subscription_id
 * @property int $tenant_id
 * @property int $user_id
 * @property float $amount
 * @property string $payment_method (pix_online, picpay, pix_offline, credit_card)
 * @property string $status (pending, under_review, approved, rejected)
 * @property Carbon $due_date
 * @property Carbon|null $paid_at
 * @property string|null $pix_code
 * @property string|null $receipt_path
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $table = 'subscription_payments';

    protected $fillable = [
        'tenant_subscription_id',
        'tenant_id',
        'user_id',
        'amount',
        'payment_method',
        'status',
        'due_date',
        'paid_at',
        'pix_code',
        'receipt_path',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tenant_subscription_id' => 'integer',
            'tenant_id' => 'integer',
            'user_id' => 'integer',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'reviewed_by' => 'integer',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
