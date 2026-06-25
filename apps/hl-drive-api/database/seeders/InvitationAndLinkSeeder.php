<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccessLevel;
use App\Enums\InvitationStatus;
use App\Enums\LinkStatus;
use App\Models\Invitation;
use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * InvitationAndLinkSeeder
 *
 * Seeds invitations and instructor-student links for testing.
 *
 * Creates:
 * - 3 sample invitations (PENDING, ACCEPTED, REJECTED)
 * - 15 active instructor-student links
 * - Distributed across 3 instructors and 5 students
 */
class InvitationAndLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'default-tenant')->firstOrFail();

        // Get instructors and students
        $instructors = User::where('email', 'like', 'instructor%@example.com')
            ->orderBy('id')
            ->get();
        $students = User::where('email', 'like', 'student%@example.com')
            ->orderBy('id')
            ->get();

        // Validate we have enough users
        if ($instructors->count() < 3 || $students->count() < 5) {
            $this->command->warn('Not enough instructors or students. Skipping seeding.');
            return;
        }

        // Create 3 sample invitations
        $this->createSampleInvitations($tenant, $instructors, $students);

        // Create 15 active links
        $this->createActiveLinks($tenant, $instructors, $students);
    }

    /**
     * Create sample invitations in different states.
     *
     * @param Tenant $tenant
     * @param \Illuminate\Database\Eloquent\Collection $instructors
     * @param \Illuminate\Database\Eloquent\Collection $students
     *
     * @return void
     */
    protected function createSampleInvitations(Tenant $tenant, $instructors, $students): void
    {
        // PENDING invitation
        Invitation::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'instructor_id' => $instructors[0]->id,
                'email' => 'pending-student@example.com',
            ],
            [
                'status' => InvitationStatus::PENDING,
                'token' => hash('sha256', Str::random(32)),
                'expires_at' => now()->addDays(7),
            ]
        );

        // ACCEPTED invitation
        $acceptedInvitation = Invitation::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'instructor_id' => $instructors[1]->id,
                'student_id' => $students[0]->id,
            ],
            [
                'status' => InvitationStatus::ACCEPTED,
                'token' => hash('sha256', Str::random(32)),
                'expires_at' => now()->addDays(7),
                'accepted_at' => now(),
            ]
        );

        // REJECTED invitation
        Invitation::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'instructor_id' => $instructors[2]->id,
                'student_id' => $students[1]->id,
            ],
            [
                'status' => InvitationStatus::REJECTED,
                'token' => hash('sha256', Str::random(32)),
                'expires_at' => now()->addDays(7),
                'rejected_at' => now(),
            ]
        );
    }

    /**
     * Create 15 active instructor-student links.
     *
     * @param Tenant $tenant
     * @param \Illuminate\Database\Eloquent\Collection $instructors
     * @param \Illuminate\Database\Eloquent\Collection $students
     *
     * @return void
     */
    protected function createActiveLinks(Tenant $tenant, $instructors, $students): void
    {
        $linkCount = 0;
        $maxLinks = 15;

        foreach ($instructors as $instructor) {
            foreach ($students as $student) {
                if ($linkCount >= $maxLinks) {
                    break 2;
                }

                InstructorStudentLink::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'instructor_id' => $instructor->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => LinkStatus::ACTIVE,
                        'access_level' => AccessLevel::FULL,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $linkCount++;
            }
        }
    }
}
