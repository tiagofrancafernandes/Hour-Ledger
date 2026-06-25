<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AccessLevel;
use App\Enums\LinkStatus;
use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * CreateInstructorStudentLink Command
 *
 * Creates an instructor-student link manually from CLI.
 * Validates both users and tenant exist before creating link.
 *
 * Usage:
 *   php artisan instructor:link:create --instructor=1 --student=2 --tenant=1
 *
 * Optional flags:
 *   --access-level=FULL (default: FULL)
 *   --status=ACTIVE (default: ACTIVE)
 */
class CreateInstructorStudentLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instructor:link:create
        {--instructor= : Instructor user ID}
        {--student= : Student user ID}
        {--tenant= : Tenant ID}
        {--access-level=FULL : Access level (BASIC, FULL, CUSTOM)}
        {--status=ACTIVE : Link status (ACTIVE, SUSPENDED, REVOKED)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an instructor-student link manually';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $instructorId = $this->option('instructor');
        $studentId = $this->option('student');
        $tenantId = $this->option('tenant');
        $accessLevel = $this->option('access-level');
        $status = $this->option('status');

        // Validate options
        if (!$instructorId || !$studentId || !$tenantId) {
            $this->error('Missing required options: --instructor, --student, --tenant');
            return self::FAILURE;
        }

        // Validate instructor exists
        $instructor = User::find($instructorId);
        if (!$instructor) {
            $this->error("Instructor with ID {$instructorId} not found");
            return self::FAILURE;
        }

        // Validate student exists
        $student = User::find($studentId);
        if (!$student) {
            $this->error("Student with ID {$studentId} not found");
            return self::FAILURE;
        }

        // Validate tenant exists
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID {$tenantId} not found");
            return self::FAILURE;
        }

        // Validate instructor is not the student
        if ($instructor->id === $student->id) {
            $this->error('Instructor cannot be the same as student');
            return self::FAILURE;
        }

        // Validate access level
        try {
            $accessLevelEnum = AccessLevel::from($accessLevel);
        } catch (\ValueError $e) {
            $this->error("Invalid access level: {$accessLevel}");
            $this->info('Valid options: BASIC, FULL, CUSTOM');
            return self::FAILURE;
        }

        // Validate status
        try {
            $statusEnum = LinkStatus::from($status);
        } catch (\ValueError $e) {
            $this->error("Invalid status: {$status}");
            $this->info('Valid options: ACTIVE, SUSPENDED, REVOKED');
            return self::FAILURE;
        }

        // Check if link already exists
        $existingLink = InstructorStudentLink::where('tenant_id', $tenantId)
            ->where('instructor_id', $instructorId)
            ->where('student_id', $studentId)
            ->withTrashed()
            ->first();

        if ($existingLink && !$existingLink->trashed()) {
            $this->error('Active link already exists between these users');
            return self::FAILURE;
        }

        // Create the link
        $link = InstructorStudentLink::create([
            'tenant_id' => $tenantId,
            'instructor_id' => $instructorId,
            'student_id' => $studentId,
            'status' => $statusEnum,
            'access_level' => $accessLevelEnum,
        ]);

        $this->info("✓ Link created successfully!");
        $this->line("  ID: {$link->id}");
        $this->line("  Instructor: {$instructor->name} ({$instructor->email})");
        $this->line("  Student: {$student->name} ({$student->email})");
        $this->line("  Status: {$status}");
        $this->line("  Access Level: {$accessLevel}");

        return self::SUCCESS;
    }
}
