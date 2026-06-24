<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use JsonSerializable;

/**
 * TenantContext represents the active tenant context during a request.
 *
 * This class is not a database model, but rather an in-memory representation
 * of the current tenant being accessed. It is used to pass tenant information
 * through the request lifecycle.
 *
 * @property int $tenantId The ID of the active tenant
 * @property string $schema The database schema name for the tenant
 * @property int|null $userId The ID of the authenticated user
 * @property Carbon $timestamp When this context was created
 */
class TenantContext implements JsonSerializable
{
    /**
     * The ID of the active tenant.
     *
     * @var int
     */
    private int $tenantId;

    /**
     * The database schema name for the tenant.
     *
     * @var string
     */
    private string $schema;

    /**
     * The ID of the authenticated user accessing this tenant.
     *
     * @var int|null
     */
    private ?int $userId;

    /**
     * The timestamp when this context was created.
     *
     * @var Carbon
     */
    private Carbon $timestamp;

    /**
     * Create a new TenantContext instance.
     *
     * @param int $tenantId The ID of the active tenant
     * @param string $schema The database schema name for the tenant
     * @param int|null $userId The ID of the authenticated user
     */
    public function __construct(int $tenantId, string $schema, ?int $userId = null)
    {
        $this->tenantId = $tenantId;
        $this->schema = $schema;
        $this->userId = $userId;
        $this->timestamp = Carbon::now();
    }

    /**
     * Get the tenant ID.
     *
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * Get the database schema name.
     *
     * @return string
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * Get the user ID.
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get the timestamp when context was created.
     *
     * @return Carbon
     */
    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    /**
     * Convert context to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'schema' => $this->schema,
            'userId' => $this->userId,
            'timestamp' => $this->timestamp->toIso8601String(),
        ];
    }

    /**
     * Convert context to JSON serializable array.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get string representation for logging.
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            'TenantContext(tenantId=%d, schema=%s, userId=%s)',
            $this->tenantId,
            $this->schema,
            $this->userId ?? 'null'
        );
    }
}
