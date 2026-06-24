<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Create Tenant Schema Command.
 *
 * Creates a new tenant schema in PostgreSQL following the naming convention:
 * tenant_{id}_{environment}
 *
 * Usage:
 *   php artisan tenancy:create-tenant {tenant-id} {name} {--environment=prod}
 *
 * Examples:
 *   php artisan tenancy:create-tenant 1 "My Tenant"
 *   php artisan tenancy:create-tenant 1 "My Tenant" --environment=staging
 */
class CreateTenantSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenancy:create-tenant
        {tenant-id : Numeric tenant ID}
        {name : Tenant display name}
        {--environment=prod : Tenant environment (dev, staging, prod)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant schema in PostgreSQL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Retrieve and validate inputs
        $tenantId = (int) $this->argument('tenant-id');
        $tenantName = (string) $this->argument('name');
        $environment = (string) $this->option('environment');

        // Validate inputs
        $validationError = $this->validateInputs($tenantId, $tenantName, $environment);
        if ($validationError !== null) {
            $this->error($validationError);
            return self::FAILURE;
        }

        // Check if tenant already exists in database
        $existingTenant = Tenant::find($tenantId);
        if ($existingTenant !== null) {
            $this->error("Tenant with ID {$tenantId} already exists in database");
            return self::FAILURE;
        }

        // Create or update tenant in database
        $this->info('Creating tenant record in database...');
        $result = $this->createTenantRecord($tenantId, $tenantName);

        if (!$result) {
            $this->error('Failed to create tenant record in database');
            return self::FAILURE;
        }

        // Create schema in PostgreSQL
        $this->info("Creating PostgreSQL schema for tenant {$tenantId}...");
        $createResult = $this->createPostgresSchema($tenantId, $tenantName, $environment);

        if (!$createResult['success']) {
            $this->handleSchemaCreationFailure($tenantId, $createResult['message']);
            return self::FAILURE;
        }

        // Display success information
        $this->displaySuccessInformation($tenantId, $tenantName, $environment, $createResult);

        return self::SUCCESS;
    }

    /**
     * Validate command inputs.
     */
    private function validateInputs(int $tenantId, string $tenantName, string $environment): ?string
    {
        if ($tenantId <= 0) {
            return 'Error: tenant-id must be a positive integer';
        }

        if (trim($tenantName) === '') {
            return 'Error: name cannot be empty';
        }

        $validEnvironments = ['dev', 'staging', 'prod'];
        if (!in_array($environment, $validEnvironments, true)) {
            return sprintf(
                'Error: environment must be one of: %s',
                implode(', ', $validEnvironments)
            );
        }

        return null;
    }

    /**
     * Create tenant record in the global database.
     */
    private function createTenantRecord(int $tenantId, string $tenantName): bool
    {
        try {
            Tenant::create([
                'id' => $tenantId,
                'name' => $tenantName,
                'slug' => $this->generateSlug($tenantName),
                'status' => 'active',
            ]);

            return true;
        } catch (\Exception $exception) {
            $this->error(sprintf('Database error: %s', $exception->getMessage()));
            return false;
        }
    }

    /**
     * Generate a URL-friendly slug from tenant name.
     */
    private function generateSlug(string $tenantName): string
    {
        $slug = strtolower(trim($tenantName));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Create schema in PostgreSQL using the database function.
     *
     * @return array<string, mixed>
     */
    private function createPostgresSchema(int $tenantId, string $tenantName, string $environment): array
    {
        try {
            $result = DB::selectOne(
                'SELECT * FROM create_tenant_schema(?, ?, ?)',
                [$tenantId, $tenantName, $environment]
            );

            if ($result === null) {
                return [
                    'success' => false,
                    'message' => 'No result returned from create_tenant_schema function',
                ];
            }

            return [
                'success' => (bool) $result->success,
                'schema_name' => (string) $result->schema_name,
                'message' => (string) $result->message,
            ];
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' => sprintf('PostgreSQL error: %s', $exception->getMessage()),
            ];
        }
    }

    /**
     * Handle failure in schema creation with rollback.
     */
    private function handleSchemaCreationFailure(int $tenantId, string $errorMessage): void
    {
        $this->error("Schema creation failed: {$errorMessage}");
        $this->info('Rolling back tenant record...');

        try {
            Tenant::destroy($tenantId);
            $this->info('Tenant record rolled back successfully');
        } catch (\Exception $exception) {
            $this->error(sprintf('Rollback error: %s', $exception->getMessage()));
        }
    }

    /**
     * Display success information about the created tenant.
     *
     * @param array<string, mixed> $createResult
     */
    private function displaySuccessInformation(
        int $tenantId,
        string $tenantName,
        string $environment,
        array $createResult
    ): void {
        $this->newLine();
        $this->info('Tenant created successfully!');
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['Tenant ID', $tenantId],
                ['Tenant Name', $tenantName],
                ['Environment', $environment],
                ['Schema Name', $createResult['schema_name']],
                ['Status', 'Active'],
            ]
        );

        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Run migrations for the tenant: php artisan migrate:tenant');
        $this->line('2. Seed initial data if needed: php artisan db:seed --tenant=' . $tenantId);
        $this->newLine();
    }
}
