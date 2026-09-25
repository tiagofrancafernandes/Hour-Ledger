<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => function (array $attributes): int {
                if (isset($attributes['client_id'])) {
                    if ($attributes['client_id'] instanceof Client) {
                        return (int) $attributes['client_id']->tenant_id;
                    }

                    if (is_numeric($attributes['client_id'])) {
                        $client = Client::withoutGlobalScopes()->find((int) $attributes['client_id']);

                        if ($client !== null && $client->tenant_id) {
                            return (int) $client->tenant_id;
                        }
                    }
                }

                return $this->getOrCreateTenantId();
            },
            'client_id' => function (array $attributes): int {
                $tenantId = $attributes['tenant_id'] ?? $this->getOrCreateTenantId();

                return Client::factory()->create(['tenant_id' => $tenantId])->id;
            },
            'name' => fake()->words(2, true) . ' Wallet',
            'description' => fake()->optional()->sentence(),
            'hourly_rate_reference' => fake()->randomFloat(2, 50, 200),
            'currency_code' => 'BRL',
            'credit_purchase_allowed' => false,
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
