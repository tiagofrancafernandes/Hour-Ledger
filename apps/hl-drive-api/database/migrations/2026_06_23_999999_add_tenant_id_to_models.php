<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add tenant_id column to multi-tenant models.
 *
 * This migration adds the tenant_id column to:
 * - clients
 * - wallets
 * - ledger_entries
 *
 * These models now support automatic tenant isolation via BelongsToTenant trait.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add tenant_id to clients table
        Schema::table('clients', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->index('tenant_id');
        });

        // Add tenant_id to wallets table
        Schema::table('wallets', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->index('tenant_id');
        });

        // Add tenant_id to ledger_entries table
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->unsignedBigInteger('tenant_id')
                ->nullable()
                ->after('id');

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table): void {
            $table->dropForeignKey(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropForeignKey(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropForeignKey(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
