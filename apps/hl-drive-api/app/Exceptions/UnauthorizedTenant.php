<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

/**
 * Exception thrown when a user is not authorized to access a tenant.
 *
 * This exception is raised when the authenticated user attempts to access
 * a tenant that they do not have permission to access.
 */
class UnauthorizedTenant extends Exception
{
    /**
     * The HTTP status code for this exception.
     *
     * @var int
     */
    protected $code = Response::HTTP_FORBIDDEN;

    /**
     * Create a new UnauthorizedTenant instance.
     *
     * @param int $tenantId The tenant ID being accessed
     * @param int|null $userId The user ID attempting access
     */
    public function __construct(int $tenantId, ?int $userId = null)
    {
        $message = sprintf('User is not authorized to access tenant %d.', $tenantId);

        if ($userId !== null) {
            $message = sprintf('User %d is not authorized to access tenant %d.', $userId, $tenantId);
        }

        parent::__construct($message, $this->code);
    }
}
