<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add tenant_id to personal_access_tokens table.
 *
 * This migration adds optional tenant_id column to personal_access_tokens table.
 *
 * When tenant_id is NULL:
 * - Token can be used globally (access to any tenant user has access to)
 *
 * When tenant_id is set:
 * - Token is limited to that specific tenant
 * - All requests with this token must include matching X-Tenant-ID header
 * - Provides security boundary for tenant-specific operations
 *
 * Composed index (tokenable_id, tokenable_type, tenant_id) allows efficient
 * lookup of tokens by tokenable resource and optional tenant scope.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            // Add tenant_id column
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('token')
                ->index()
                ->comment('Optional tenant scope: NULL = global, set = tenant-specific');

            // Add composed index for efficient token lookup
            $table->index(['tokenable_id', 'tokenable_type', 'tenant_id'])
                ->comment('Composite index for token lookup by tokenable and tenant');

            // Add foreign key to tenants table
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            // Drop foreign key first
            $table->dropForeign(['tenant_id']);

            // Drop indices
            $table->dropIndex(['tokenable_id', 'tokenable_type', 'tenant_id']);
            $table->dropIndex(['tenant_id']);

            // Drop column
            $table->dropColumn('tenant_id');
        });
    }
};
