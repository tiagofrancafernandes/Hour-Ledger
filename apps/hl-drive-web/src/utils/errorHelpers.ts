/**
 * Extract meaningful error message from API error response
 * Handles both Laravel validation errors (422) and general error responses
 *
 * Response structure:
 * {
 *   "message": "The given data was invalid.",
 *   "errors": {
 *     "password": ["The password field must be at least 8 characters."],
 *     "email": ["The email has already been taken."]
 *   }
 * }
 */
export function extractErrorMessage(error: unknown): string {
    // Handle Error objects
    if (error instanceof Error) {
        return error.message;
    }

    // Handle unknown type
    if (typeof error !== 'object' || error === null) {
        return 'An error occurred';
    }

    const errorObj = error as Record<string, unknown>;

    // Check for validation errors object with field-specific messages
    if (errorObj.errors && typeof errorObj.errors === 'object') {
        const errors = errorObj.errors as Record<string, unknown>;
        const firstFieldError = getFirstFieldError(errors);

        if (firstFieldError) {
            return firstFieldError;
        }
    }

    // Check for general message field
    if (errorObj.message && typeof errorObj.message === 'string') {
        return errorObj.message;
    }

    return 'An error occurred';
}

/**
 * Extract the first validation error from the errors object
 *
 * Returns the first error message from the first field that has errors
 */
function getFirstFieldError(errors: Record<string, unknown>): string | null {
    for (const fieldName of Object.keys(errors)) {
        const fieldErrors = errors[fieldName];

        // Field errors are typically an array of strings
        if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
            const firstError = fieldErrors[0];

            if (typeof firstError === 'string') {
                return firstError;
            }
        }
    }

    return null;
}

/**
 * Extract all validation errors from API response
 * Returns an object mapping field names to their error messages
 */
export function extractAllErrors(error: unknown): Record<string, string> {
    if (typeof error !== 'object' || error === null) {
        return {};
    }

    const errorObj = error as Record<string, unknown>;

    if (errorObj.errors && typeof errorObj.errors === 'object') {
        const errors = errorObj.errors as Record<string, unknown>;
        const result: Record<string, string> = {};

        for (const fieldName of Object.keys(errors)) {
            const fieldErrors = errors[fieldName];

            if (Array.isArray(fieldErrors) && fieldErrors.length > 0) {
                const firstError = fieldErrors[0];

                if (typeof firstError === 'string') {
                    result[fieldName] = firstError;
                }
            }
        }

        return result;
    }

    return {};
}
