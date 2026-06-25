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
     * Adds active instructor context to users table.
     *
     * active_instructor_id allows a student to designate which instructor's
     * resources they are currently viewing/working with. This optimizes
     * filtering and reduces need for X-Instructor-ID headers in many cases.
     *
     * Nullable: Student viewing their own resources has NULL active_instructor_id
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->unsignedBigInteger('active_instructor_id')
                ->nullable()
                ->after('email');

            $table->foreign('active_instructor_id')
                ->references('id')
                ->on('users')
                ->nullableOnDelete();

            // Index for common queries filtering by active instructor
            $table->index('active_instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropForeign(['active_instructor_id']);
            $table->dropColumn('active_instructor_id');
        });
    }
};
