<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'instructor_id' => User::factory(),
            'name' => fake()->words(3, true) . ' Pacote',
            'description' => fake()->sentence(),
            'hours' => fake()->randomFloat(2, 5, 30),
            'price' => fake()->randomFloat(2, 300, 2500),
            'currency_code' => 'BRL',
            'active' => true,
        ];
    }
}
