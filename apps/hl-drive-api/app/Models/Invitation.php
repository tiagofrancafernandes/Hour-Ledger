<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Invitation Model.
 *
 * Represents an invitation for an instructor to link with a student.
 * Manages the state of invitations from creation through acceptance/rejection.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $instructor_id
 * @property int|null $student_id
 * @property string|null $email
 * @property InvitationStatus $status
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $rejected_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $instructor
 * @property-read User|null $student
 * @property-read InstructorStudentLink|null $link
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation accepted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation byInstructor(int $instructorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation forTenant(int $tenantId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation notExpired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation expired()
 *
 * @mixin \Eloquent
 */
class Invitation extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invitations';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'instructor_id',
        'student_id',
        'email',
        'status',
        'token',
        'expires_at',
        'accepted_at',
        'rejected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant this invitation belongs to.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the instructor who created this invitation.
     *
     * @return BelongsTo
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the student this invitation was sent to (if linked).
     *
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the link created from this invitation (if accepted).
     *
     * @return HasOne
     */
    public function link(): HasOne
    {
        return $this->hasOne(InstructorStudentLink::class, 'invitation_id');
    }

    /**
     * Scope: Get only pending invitations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', InvitationStatus::PENDING);
    }

    /**
     * Scope: Get only accepted invitations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccepted(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', InvitationStatus::ACCEPTED);
    }

    /**
     * Scope: Get only rejected invitations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', InvitationStatus::REJECTED);
    }

    /**
     * Scope: Filter by instructor.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $instructorId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByInstructor(\Illuminate\Database\Eloquent\Builder $query, int $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope: Filter by tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tenantId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Get only non-expired invitations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Get only expired invitations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if invitation is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Check if invitation is still pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING;
    }

    /**
     * Check if invitation is resolvable (still pending and not expired).
     *
     * @return bool
     */
    public function isResolvable(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    /**
     * Accept the invitation.
     *
     * Transitions from PENDING to ACCEPTED state.
     *
     * @return bool
     */
    public function accept(): bool
    {
        return $this->update([
            'status' => InvitationStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Reject the invitation.
     *
     * Transitions from PENDING to REJECTED state.
     *
     * @return bool
     */
    public function reject(): bool
    {
        return $this->update([
            'status' => InvitationStatus::REJECTED,
            'rejected_at' => now(),
        ]);
    }
}
