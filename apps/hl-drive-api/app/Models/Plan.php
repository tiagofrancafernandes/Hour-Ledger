<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan Model (SaaS Level)
 *
 * Represents a subscription plan available for instructors (tenants).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property numeric $price
 * @property int $interval_days
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereIntervalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'interval_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'interval_days' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
