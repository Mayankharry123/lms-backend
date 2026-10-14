<?php
/**
 * ErrorResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Request;

class ErrorResource
{
    /**
     * Create error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @param string $errorCode
     * @return array
     */
    public static function make(string $message = 'Error', $errors = null, int $statusCode = 400, string $errorCode = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'error_code' => $errorCode,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'status_code' => $statusCode,
            ]
        ];
    }

    /**
     * Validation error response
     *
     * @param array $errors
     * @param string $message
     * @return array
     */
    public static function validation(array $errors, string $message = 'Validation failed'): array
    {
        return self::make($message, $errors, 422, 'VALIDATION_ERROR');
    }

    /**
     * Unauthorized error response
     *
     * @param string $message
     * @return array
     */
    public static function unauthorized(string $message = 'Unauthorized'): array
    {
        return self::make($message, null, 401, 'UNAUTHORIZED');
    }

    /**
     * Forbidden error response
     *
     * @param string $message
     * @return array
     */
    public static function forbidden(string $message = 'Forbidden'): array
    {
        return self::make($message, null, 403, 'FORBIDDEN');
    }

    /**
     * Not found error response
     *
     * @param string $message
     * @return array
     */
    public static function notFound(string $message = 'Resource not found'): array
    {
        return self::make($message, null, 404, 'NOT_FOUND');
    }

    /**
     * Server error response
     *
     * @param string $message
     * @param mixed $errors
     * @return array
     */
    public static function serverError(string $message = 'Internal server error', $errors = null): array
    {
        return self::make($message, $errors, 500, 'SERVER_ERROR');
    }
}
