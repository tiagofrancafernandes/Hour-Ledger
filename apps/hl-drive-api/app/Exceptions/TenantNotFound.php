<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

/**
 * Exception thrown when a tenant is not found.
 *
 * This exception is raised when attempting to resolve a tenant
 * that does not exist in the database.
 */
class TenantNotFound extends Exception
{
    /**
     * The HTTP status code for this exception.
     *
     * @var int
     */
    protected $code = Response::HTTP_FORBIDDEN;

    /**
     * Create a new TenantNotFound instance.
     *
     * @param int|null $tenantId The tenant ID that was not found
     */
    public function __construct(?int $tenantId = null)
    {
        $message = 'Tenant not found.';

        if ($tenantId !== null) {
            $message = sprintf('Tenant with ID %d not found.', $tenantId);
        }

        parent::__construct($message, $this->code);
    }
}
