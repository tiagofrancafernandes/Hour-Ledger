<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * TenantFactory provides test data for Tenant models.
 *
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Tenant>
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'status' => TenantStatus::ACTIVE,
        ];
    }

    /**
     * State for an active tenant.
     *
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::ACTIVE,
        ]);
    }

    /**
     * State for a suspended tenant.
     *
     * @return static
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::SUSPENDED,
        ]);
    }

    /**
     * State for a deleted tenant.
     *
     * @return static
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::DELETED,
        ]);
    }
}
