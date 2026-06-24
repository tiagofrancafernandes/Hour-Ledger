<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create tenants table.
 *
 * This migration creates the tenants table in the public schema.
 * The tenants table is global (not tenantized) and stores configuration
 * for all tenants in the system.
 *
 * Naming Convention for Tenant Schemas:
 *   tenant_{id}_{environment}
 *   Examples: tenant_1_prod, tenant_1_staging, tenant_1_dev
 *
 * Status Enum Values:
 *   - active: Tenant is operational and accessible
 *   - suspended: Tenant is suspended, no access to data
 *   - deleted: Tenant is soft-deleted, schema may be archived
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->comment('Global tenants configuration table (public schema)');

            // Primary Key
            $table->bigIncrements('id')
                ->comment('Unique tenant identifier');

            // Tenant Information
            $table->string('name', 255)
                ->comment('Tenant display name');

            $table->string('slug', 100)
                ->unique()
                ->nullable()
                ->comment('URL-friendly tenant identifier');

            // Status Management
            $table->string('status', 50)
                ->default('active')
                ->index()
                ->comment('Tenant status: active, suspended, or deleted');

            // Timestamps
            $table->timestamps()
                ->comment('Created and updated timestamps');

            // Additional Information
            $table->text('metadata')
                ->nullable()
                ->comment('Additional tenant configuration in JSON format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
