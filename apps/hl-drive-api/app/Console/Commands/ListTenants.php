<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * List Tenants Command.
 *
 * Displays all tenants in the system with their configuration and status.
 *
 * Output includes:
 * - Tenant ID, Name, Status
 * - Schema name pattern (tenant_{id}_{environment})
 * - Creation date
 * - Data statistics (optional)
 *
 * Usage:
 *   php artisan tenancy:list
 *   php artisan tenancy:list --include-stats
 */
class ListTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenancy:list
        {--include-stats : Include data statistics for each tenant}
        {--status= : Filter by status (active, suspended, deleted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tenants in the system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Loading tenants...');
        $this->newLine();

        // Build query
        $query = Tenant::query();

        // Apply status filter if provided
        $statusFilter = $this->option('status');
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        // Retrieve all tenants
        $tenants = $query->orderBy('id')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found');
            return self::SUCCESS;
        }

        // Build table rows
        $rows = [];
        foreach ($tenants as $tenant) {
            $row = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status->value,
                'slug' => $tenant->slug ?? '-',
                'created' => $tenant->created_at->format('Y-m-d H:i'),
            ];

            if ($this->option('include-stats')) {
                $stats = $this->getSchemaStatistics((int) $tenant->id);
                $row['tables'] = $stats['table_count'];
                $row['rows'] = $stats['row_count'];
            }

            $rows[] = $row;
        }

        // Display table
        $headers = array_keys($rows[0] ?? []);
        $this->table($headers, $rows);

        // Display summary
        $this->displaySummary($tenants);

        return self::SUCCESS;
    }

    /**
     * Get statistics about a tenant schema.
     *
     * @return array<string, int>
     */
    private function getSchemaStatistics(int $tenantId): array
    {
        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            return ['table_count' => 0, 'row_count' => 0];
        }

        try {
            $environment = 'prod';
            $schemaName = $tenant->schemaName($environment);

            // Count tables in schema
            $tableCount = DB::selectOne(
                "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?",
                [$schemaName]
            )?->count ?? 0;

            // Count total rows across all tables
            $rowCount = 0;
            $tables = DB::select(
                "SELECT tablename FROM pg_tables WHERE schemaname = ?",
                [$schemaName]
            );

            foreach ($tables as $table) {
                $count = DB::selectOne(
                    sprintf('SELECT COUNT(*) as count FROM %s.%s', $schemaName, $table->tablename)
                )?->count ?? 0;
                $rowCount += $count;
            }

            return [
                'table_count' => (int) $tableCount,
                'row_count' => (int) $rowCount,
            ];
        } catch (\Exception $exception) {
            $this->warn(sprintf('Error getting stats for tenant %d: %s', $tenantId, $exception->getMessage()));
            return ['table_count' => 0, 'row_count' => 0];
        }
    }

    /**
     * Display summary information.
     */
    private function displaySummary(\Illuminate\Support\Collection $tenants): void
    {
        $this->newLine();
        $this->line('Summary:');

        $activeCount = $tenants->where('status', 'active')->count();
        $suspendedCount = $tenants->where('status', 'suspended')->count();
        $deletedCount = $tenants->where('status', 'deleted')->count();

        $this->table(
            ['Status', 'Count'],
            [
                ['Active', $activeCount],
                ['Suspended', $suspendedCount],
                ['Deleted', $deletedCount],
                ['Total', $tenants->count()],
            ]
        );
    }
}
