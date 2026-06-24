<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TenantNotActive;
use App\Exceptions\TenantNotFound;
use App\Models\Tenant;
use App\Models\TenantContext;

/**
 * TenantResolver is a singleton service that manages the active tenant context.
 *
 * This service resolves which tenant is being accessed during a request
 * and provides access to tenant information throughout the request lifecycle.
 *
 * Usage:
 *     $resolver = app(TenantResolver::class);
 *     $resolver->setTenantId($id);
 *     $schema = $resolver->getSchema();
 *     $resolver->clear();
 */
class TenantResolver
{
    /**
     * The ID of the currently active tenant.
     *
     * @var int|null
     */
    private ?int $tenantId = null;

    /**
     * The database schema name for the active tenant.
     *
     * @var string|null
     */
    private ?string $schema = null;

    /**
     * The ID of the authenticated user accessing the tenant.
     *
     * @var int|null
     */
    private ?int $userId = null;

    /**
     * The active tenant context.
     *
     * @var TenantContext|null
     */
    private ?TenantContext $context = null;

    /**
     * Environment for schema naming convention.
     *
     * @var string
     */
    private string $environment;

    /**
     * Create a new TenantResolver instance.
     *
     * @param string $environment The environment for schema naming (default: 'prod')
     */
    public function __construct(string $environment = 'prod')
    {
        $this->environment = $environment;
    }

    /**
     * Set the active tenant ID.
     *
     * Validates that the tenant exists and is active before setting.
     *
     * @param int $tenantId The tenant ID to set as active
     * @param int|null $userId The authenticated user ID (optional)
     *
     * @throws TenantNotFound If tenant does not exist
     * @throws TenantNotActive If tenant is not in active state
     *
     * @return void
     */
    public function setTenantId(int $tenantId, ?int $userId = null): void
    {
        $tenant = Tenant::find($tenantId);

        if ($tenant === null) {
            throw new TenantNotFound($tenantId);
        }

        if (!$tenant->isActive()) {
            throw new TenantNotActive($tenantId, $tenant->status);
        }

        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->schema = $tenant->schemaName($this->environment);
        $this->context = new TenantContext($tenantId, $this->schema, $userId);
    }

    /**
     * Get the currently active tenant ID.
     *
     * @return int|null
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Check if a tenant is currently set and active.
     *
     * @return bool
     */
    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    /**
     * Get the database schema name for the active tenant.
     *
     * Returns the schema name in the format: tenant_{id}_{environment}
     *
     * @throws TenantNotFound If no tenant is currently active
     *
     * @return string
     */
    public function getSchema(): string
    {
        if ($this->schema === null) {
            throw new TenantNotFound();
        }

        return $this->schema;
    }

    /**
     * Get the user ID currently accessing the tenant.
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get the current tenant context.
     *
     * @throws TenantNotFound If no tenant context is currently active
     *
     * @return TenantContext
     */
    public function getContext(): TenantContext
    {
        if ($this->context === null) {
            throw new TenantNotFound();
        }

        return $this->context;
    }

    /**
     * Clear the active tenant context.
     *
     * This should be called at the end of a request to clean up state.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->tenantId = null;
        $this->schema = null;
        $this->userId = null;
        $this->context = null;
    }

    /**
     * Set the environment for schema naming.
     *
     * @param string $environment The environment name (dev, staging, prod)
     *
     * @return void
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;

        if ($this->tenantId !== null && $this->context !== null) {
            $tenant = Tenant::find($this->tenantId);

            if ($tenant !== null) {
                $this->schema = $tenant->schemaName($environment);
            }
        }
    }
}
