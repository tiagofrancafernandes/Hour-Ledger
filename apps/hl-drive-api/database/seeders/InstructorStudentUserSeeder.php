<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * InstructorStudentUserSeeder
 *
 * Seeds instructor and student users for testing instructor-student
 * linking functionality.
 *
 * Creates:
 * - 3 instructors
 * - 5 students
 * - Links all users to default tenant
 */
class InstructorStudentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'default-tenant')->firstOrFail();

        // Create 3 instructors
        $instructors = [];
        for ($i = 1; $i <= 3; $i++) {
            $instructor = User::firstOrCreate(
                ['email' => "instructor{$i}@example.com"],
                [
                    'name' => "Instructor {$i}",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Link to tenant
            $this->linkUserToTenant($instructor, $tenant);

            $instructors[] = $instructor;
        }

        // Create 5 students
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $student = User::firstOrCreate(
                ['email' => "student{$i}@example.com"],
                [
                    'name' => "Student {$i}",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Link to tenant
            $this->linkUserToTenant($student, $tenant);

            $students[] = $student;
        }
    }

    /**
     * Link user to tenant with active status.
     *
     * @param User $user
     * @param Tenant $tenant
     *
     * @return void
     */
    protected function linkUserToTenant(User $user, Tenant $tenant): void
    {
        DB::table('user_tenants')->updateOrInsert(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'role' => 'user',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
