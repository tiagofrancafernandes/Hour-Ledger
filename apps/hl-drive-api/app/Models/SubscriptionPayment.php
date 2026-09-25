<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property numeric $amount
 * @property string $payment_method
 * @property string $status
 * @property \Illuminate\Support\Carbon $due_date
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $pix_code
 * @property string|null $receipt_path
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $reviewer
 * @property-read TenantSubscription $subscription
 * @property-read Tenant $tenant
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment wherePixCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereReceiptPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereTenantSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SubscriptionPayment whereUserId($value)
 * @mixin \Eloquent
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
