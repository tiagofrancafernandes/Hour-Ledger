<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create user_tenants pivot table.
 *
 * This migration creates the user_tenants table in the public schema.
 * It defines the many-to-many relationship between users and tenants,
 * allowing a user to have access to multiple tenants.
 *
 * The relationship tracks:
 * - user_id: Global user identifier
 * - tenant_id: Tenant identifier
 * - role: Role within this tenant (optional, for future role per tenant)
 * - created_at: When access was granted
 *
 * Status can be used to soft-delete access without removing the record.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_tenants', function (Blueprint $table): void {
            $table->comment('Pivot table for user-tenant relationships (public schema)');

            // Primary Key
            $table->bigIncrements('id')
                ->comment('Unique pivot record identifier');

            // Foreign Keys
            $table->unsignedBigInteger('user_id')
                ->comment('Global user identifier');

            $table->unsignedBigInteger('tenant_id')
                ->comment('Tenant identifier');

            // Access Information
            $table->string('role', 50)
                ->default('member')
                ->comment('User role within this tenant (owner, admin, member, viewer)');

            $table->string('status', 50)
                ->default('active')
                ->index()
                ->comment('Access status: active, suspended, or revoked');

            // Timestamps
            $table->timestamps();

            // Indices
            $table->unique(['user_id', 'tenant_id'])
                ->comment('Ensure user has at most one relationship per tenant');

            $table->index('user_id')
                ->comment('Query tenants by user');

            $table->index('tenant_id')
                ->comment('Query users by tenant');

            // Foreign Keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
        Schema::dropIfExists('user_tenants');
    }
};
