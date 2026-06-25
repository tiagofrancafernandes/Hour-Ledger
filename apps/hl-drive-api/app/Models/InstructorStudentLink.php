<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessLevel;
use App\Enums\LinkStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * InstructorStudentLink Model.
 *
 * Represents an active link between an instructor and student.
 * Manages access control and historical audit trail via soft delete.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $instructor_id
 * @property int $student_id
 * @property int|null $invitation_id
 * @property LinkStatus $status
 * @property AccessLevel $access_level
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Tenant|null $tenant
 * @property-read User|null $instructor
 * @property-read User|null $student
 * @property-read Invitation|null $invitation
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink suspended()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink revoked()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink byInstructor(int $instructorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink byStudent(int $studentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink forTenant(int $tenantId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InstructorStudentLink between(int $instructorId, int $studentId)
 *
 * @mixin \Eloquent
 */
class InstructorStudentLink extends Model
{
    /** @use HasFactory<\Database\Factories\InstructorStudentLinkFactory> */
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'instructor_student_links';

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
        'invitation_id',
        'status',
        'access_level',
        'revoked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LinkStatus::class,
            'access_level' => AccessLevel::class,
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant this link belongs to.
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the instructor in this link.
     *
     * @return BelongsTo
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the student in this link.
     *
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the invitation that created this link.
     *
     * @return BelongsTo
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class, 'invitation_id');
    }

    /**
     * Scope: Get only active links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', LinkStatus::ACTIVE)
            ->whereNull('deleted_at');
    }

    /**
     * Scope: Get only suspended links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', LinkStatus::SUSPENDED);
    }

    /**
     * Scope: Get only revoked links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRevoked(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', LinkStatus::REVOKED);
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
     * Scope: Filter by student.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $studentId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStudent(\Illuminate\Database\Eloquent\Builder $query, int $studentId)
    {
        return $query->where('student_id', $studentId);
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
     * Scope: Get link between specific instructor and student.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $instructorId
     * @param int $studentId
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBetween(\Illuminate\Database\Eloquent\Builder $query, int $instructorId, int $studentId)
    {
        return $query->where('instructor_id', $instructorId)
            ->where('student_id', $studentId);
    }

    /**
     * Check if link is active and not deleted.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status->isActive() && $this->deleted_at === null;
    }

    /**
     * Check if link grants access.
     *
     * Only ACTIVE status and not deleted grants access.
     *
     * @return bool
     */
    public function grantAccess(): bool
    {
        return $this->status->grantAccess() && $this->deleted_at === null;
    }

    /**
     * Revoke the link.
     *
     * Marks link as revoked and soft-deletes it.
     *
     * @return bool
     */
    public function revoke(): bool
    {
        return $this->update([
            'status' => LinkStatus::REVOKED,
            'revoked_at' => now(),
            'deleted_at' => now(),
        ]);
    }

    /**
     * Suspend the link temporarily.
     *
     * Changes status to SUSPENDED but does not soft-delete.
     *
     * @return bool
     */
    public function suspend(): bool
    {
        return $this->update([
            'status' => LinkStatus::SUSPENDED,
        ]);
    }

    /**
     * Reactivate a suspended link.
     *
     * Changes status back to ACTIVE.
     *
     * @return bool
     */
    public function reactivate(): bool
    {
        return $this->update([
            'status' => LinkStatus::ACTIVE,
        ]);
    }
}
