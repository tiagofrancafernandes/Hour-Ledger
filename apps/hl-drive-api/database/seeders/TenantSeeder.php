<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * TenantSeeder
 *
 * Seeds a default tenant for development and testing.
 */
class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['name' => 'Default Tenant'],
            [
                'slug' => 'default-tenant',
                'status' => TenantStatus::ACTIVE,
            ]
        );
    }
}
