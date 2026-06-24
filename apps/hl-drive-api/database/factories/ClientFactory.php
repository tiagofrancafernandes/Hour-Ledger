<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use App\Services\TenantResolver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => $this->getOrCreateTenantId(),
            'name' => fake()->company(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Get or create a tenant ID.
     *
     * If a tenant is already set via TenantResolver, use that.
     * Otherwise create a new tenant.
     *
     * @return int
     */
    protected function getOrCreateTenantId(): int
    {
        try {
            $tenantResolver = app(TenantResolver::class);

            if ($tenantResolver->hasTenant()) {
                return $tenantResolver->getTenantId();
            }
        } catch (\Exception $e) {
            // If container is not available, ignore and create new tenant
        }

        return Tenant::factory()->create()->id;
    }
}
