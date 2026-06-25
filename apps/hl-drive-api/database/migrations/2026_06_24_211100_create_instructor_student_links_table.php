<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the instructor_student_links table for managing
     * instructor-student relationships with access control.
     *
     * Link states:
     * - ACTIVE: Link is active, access granted
     * - SUSPENDED: Link is suspended temporarily, access denied
     * - REVOKED: Link is revoked permanently, access denied
     *
     * Soft delete preserves audit trail while removing from normal queries.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('instructor_student_links', static function (Blueprint $table) {
            $table->id();

            // Tenant context
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            // Instructor
            $table->unsignedBigInteger('instructor_id');
            $table->foreign('instructor_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Student
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Reference to the invitation that created this link
            $table->unsignedBigInteger('invitation_id')
                ->nullable();
            $table->foreign('invitation_id')
                ->references('id')
                ->on('invitations')
                ->nullableOnDelete();

            // Link status
            $table->string('status')
                ->default('ACTIVE')
                ->index();

            // Access level for future permission expansion
            $table->string('access_level')
                ->default('FULL');

            // Revocation timestamp
            $table->timestamp('revoked_at')
                ->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint on active links (not deleted)
            // Ensures only one active link per instructor-student pair per tenant
            $table->unique(['tenant_id', 'instructor_id', 'student_id'], 'unique_active_links');
            // Note: PostgreSQL partial index created via raw SQL if needed

            // Indexes for common queries
            $table->index(['tenant_id', 'student_id', 'status']);
            $table->index(['tenant_id', 'instructor_id', 'status']);
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_student_links');
    }
};
