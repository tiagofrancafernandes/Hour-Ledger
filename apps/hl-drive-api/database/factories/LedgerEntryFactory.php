<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenantId = $this->getOrCreateTenantId();
        $wallet = Wallet::factory()->for(Tenant::find($tenantId), 'tenant');

        return [
            'tenant_id' => $tenantId,
            'wallet_id' => $wallet,
            'hours' => fake()->randomFloat(2, -20, 50),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'reference_date' => fake()->dateTimeBetween('-3 months', 'now'),
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

    /**
     * Configure a credit entry (positive hours).
     */
    public function credit(?float $hours = null): static
    {
        return $this->state(fn () => [
            'hours' => $hours ?? fake()->randomFloat(2, 1, 50),
        ]);
    }

    /**
     * Configure a debit entry (negative hours).
     */
    public function debit(?float $hours = null): static
    {
        $amount = $hours ?? fake()->randomFloat(2, 1, 20);

        return $this->state(fn () => [
            'hours' => -abs($amount),
        ]);
    }
}
