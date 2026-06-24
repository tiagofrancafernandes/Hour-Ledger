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
        $tenantId = $this->getOrCreateTenantId();
        $client = Client::factory()->for(Tenant::find($tenantId), 'tenant');

        return [
            'tenant_id' => $tenantId,
            'client_id' => $client,
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
