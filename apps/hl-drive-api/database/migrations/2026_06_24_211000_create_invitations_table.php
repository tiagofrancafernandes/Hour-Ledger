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
     * Creates the invitations table for the instructor-student linking workflow.
     *
     * Invitation states:
     * - PENDING: Sent to email, awaiting student response
     * - ACCEPTED: Student accepted invitation, link created
     * - REJECTED: Student rejected invitation, no link created
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('invitations', static function (Blueprint $table) {
            $table->id();

            // Tenant context
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();

            // Instructor creating the invitation
            $table->unsignedBigInteger('instructor_id');
            $table->foreign('instructor_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Student receiving the invitation (nullable until accepted)
            $table->unsignedBigInteger('student_id')
                ->nullable();
            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->nullableOnDelete();

            // Email address for invitation (if student_id is null)
            $table->string('email')
                ->nullable();

            // Invitation status
            $table->string('status')
                ->default('PENDING')
                ->index();

            // Unique token for accepting invitation via email link
            $table->string('token')
                ->unique();

            // Token expiration
            $table->timestamp('expires_at');

            // Resolution timestamps
            $table->timestamp('accepted_at')
                ->nullable();
            $table->timestamp('rejected_at')
                ->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['tenant_id', 'instructor_id', 'status']);
            $table->index(['tenant_id', 'email', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
