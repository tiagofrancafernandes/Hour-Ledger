<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $customer_id
 * @property string|null $client_role
 * @property int|null $active_instructor_id
 * @property-read Client|null $client
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CreditPurchasePayment> $creditPurchasePayments
 * @property-read int|null $credit_purchase_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CreditPurchase> $creditPurchases
 * @property-read int|null $credit_purchases_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tenant> $tenants
 * @property-read int|null $tenants_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereClientRole($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'customer_id',
        'client_role',
        'active_instructor_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function creditPurchases()
    {
        return $this->hasMany(CreditPurchase::class, 'customer_id');
    }

    public function creditPurchasePayments()
    {
        return $this->hasMany(CreditPurchasePayment::class, 'receipt_approved_by');
    }

    /**
     * Get all tenants this user has access to.
     *
     * Users can have access to multiple tenants through the user_tenants pivot table.
     * This relationship allows querying all tenants a user is associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tenants()
    {
        return $this->belongsToMany(
            Tenant::class,
            'user_tenants',
            'user_id',
            'tenant_id'
        )
            ->withPivot('role', 'status')
            ->withTimestamps();
    }

    /**
     * Get the active instructor for this user (if any).
     *
     * Returns the user's currently selected instructor context.
     * Null if user is not viewing instructor resources.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function activeInstructor()
    {
        return $this->belongsTo(static::class, 'active_instructor_id');
    }

    /**
     * Get all invitations sent by this user as instructor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sentInvitations()
    {
        return $this->hasMany(Invitation::class, 'instructor_id');
    }

    /**
     * Get all invitations received by this user as student.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function receivedInvitations()
    {
        return $this->hasMany(Invitation::class, 'student_id');
    }

    /**
     * Get all instructor-student links where this user is the instructor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studentLinks()
    {
        return $this->hasMany(InstructorStudentLink::class, 'instructor_id');
    }

    /**
     * Get all instructor-student links where this user is the student.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instructorLinks()
    {
        return $this->hasMany(InstructorStudentLink::class, 'student_id');
    }

    /**
     * Check if user has access to a specific tenant.
     *
     * Validates that:
     * 1. User has a relationship in user_tenants table
     * 2. Access status is active (not suspended or revoked)
     * 3. Tenant itself is active or accessible
     *
     * @param int $tenantId The tenant ID to check access for
     *
     * @return bool True if user can access tenant, false otherwise
     */
    public function hasAccessToTenant(int $tenantId): bool
    {
        $userTenant = $this->tenants()
            ->where('tenant_id', $tenantId)
            ->where('user_tenants.status', 'active')
            ->wherePivot('status', 'active')
            ->first();

        if (!$userTenant) {
            return false;
        }

        // Verify tenant is accessible
        return $userTenant->allowsOperations();
    }

    /**
     * Get all tenants this user can access.
     *
     * Returns only tenants where:
     * 1. User has active relationship in user_tenants
     * 2. Tenant allows operations (active or accessible)
     *
     * Useful for populating tenant selector in UI.
     *
     * @return Collection<int, Tenant> Collection of accessible tenants
     */
    public function getAccessibleTenants(): Collection
    {
        return $this->tenants()
            ->wherePivot('status', 'active')
            ->accessible()
            ->get();
    }

    /**
     * Check if user has an active instructor link with another user.
     *
     * @param int $instructorId The instructor ID to check
     *
     * @return bool
     */
    public function hasActiveInstructorLink(int $instructorId): bool
    {
        return $this->instructorLinks()
            ->where('instructor_id', $instructorId)
            ->active()
            ->exists();
    }

    /**
     * Check if user has an active student link with another user.
     *
     * @param int $studentId The student ID to check
     *
     * @return bool
     */
    public function hasActiveStudentLink(int $studentId): bool
    {
        return $this->studentLinks()
            ->where('student_id', $studentId)
            ->active()
            ->exists();
    }
}
