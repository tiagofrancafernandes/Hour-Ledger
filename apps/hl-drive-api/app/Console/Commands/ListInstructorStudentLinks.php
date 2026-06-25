<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InstructorStudentLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * ListInstructorStudentLinks Command
 *
 * Lists instructor-student links with optional filtering.
 *
 * Usage:
 *   php artisan instructor:link:list
 *   php artisan instructor:link:list --tenant=1
 *   php artisan instructor:link:list --instructor=1
 *   php artisan instructor:link:list --student=1
 *   php artisan instructor:link:list --status=ACTIVE
 *   php artisan instructor:link:list --include-deleted
 */
class ListInstructorStudentLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instructor:link:list
        {--tenant= : Filter by tenant ID}
        {--instructor= : Filter by instructor user ID}
        {--student= : Filter by student user ID}
        {--status= : Filter by status (ACTIVE, SUSPENDED, REVOKED)}
        {--include-deleted : Include soft-deleted links}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List instructor-student links with optional filters';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $query = InstructorStudentLink::with([
            'tenant',
            'instructor',
            'student',
        ]);

        // Apply filters
        $this->applyFilters($query);

        // Include deleted if requested
        if ($this->option('include-deleted')) {
            $query = $query->withTrashed();
        }

        // Retrieve links
        $links = $query->orderBy('created_at', 'desc')->get();

        // Display results
        if ($links->isEmpty()) {
            $this->info('No instructor-student links found.');
            return self::SUCCESS;
        }

        $this->info("Found {$links->count()} instructor-student link(s):\n");

        $headers = ['ID', 'Tenant', 'Instructor', 'Student', 'Status', 'Access Level', 'Created'];
        $rows = [];

        foreach ($links as $link) {
            $createdDate = $link->created_at->format('Y-m-d H:i');
            $deletedIndicator = $link->deleted_at ? ' (DELETED)' : '';

            $rows[] = [
                $link->id,
                $link->tenant->name ?? 'N/A',
                "{$link->instructor->name} ({$link->instructor->email})",
                "{$link->student->name} ({$link->student->email})",
                $link->status->value . $deletedIndicator,
                $link->access_level->value,
                $createdDate,
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }

    /**
     * Apply query filters from command options.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return void
     */
    protected function applyFilters(\Illuminate\Database\Eloquent\Builder $query): void
    {
        // Filter by tenant
        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        // Filter by instructor
        if ($instructorId = $this->option('instructor')) {
            $query->where('instructor_id', $instructorId);
        }

        // Filter by student
        if ($studentId = $this->option('student')) {
            $query->where('student_id', $studentId);
        }

        // Filter by status
        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }
    }
}
