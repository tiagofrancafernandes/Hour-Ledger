<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\TenantStatus;
use Exception;
use Illuminate\Http\Response;

/**
 * Exception thrown when a tenant is not in an active state.
 *
 * This exception is raised when attempting to access a tenant
 * that is suspended, deleted, or otherwise inactive.
 */
class TenantNotActive extends Exception
{
    /**
     * The HTTP status code for this exception.
     *
     * @var int
     */
    protected $code = Response::HTTP_FORBIDDEN;

    /**
     * Create a new TenantNotActive instance.
     *
     * @param int $tenantId The tenant ID
     * @param TenantStatus $status The current status of the tenant
     */
    public function __construct(int $tenantId, TenantStatus $status)
    {
        $message = sprintf(
            'Tenant %d is not active. Current status: %s.',
            $tenantId,
            $status->label()
        );

        parent::__construct($message, $this->code);
    }
}
