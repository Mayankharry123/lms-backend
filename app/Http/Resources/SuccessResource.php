<?php
/**
 * SuccessResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Illuminate\Http\Request;

class SuccessResource
{
    /**
     * Create success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return array
     */
    public static function make($data = null, string $message = 'Success', int $statusCode = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'status_code' => $statusCode,
            ]
        ];
    }

    /**
     * Created response
     *
     * @param mixed $data
     * @param string $message
     * @return array
     */
    public static function created($data = null, string $message = 'Resource created successfully'): array
    {
        return self::make($data, $message, 201);
    }

    /**
     * Updated response
     *
     * @param mixed $data
     * @param string $message
     * @return array
     */
    public static function updated($data = null, string $message = 'Resource updated successfully'): array
    {
        return self::make($data, $message, 200);
    }

    /**
     * Deleted response
     *
     * @param string $message
     * @return array
     */
    public static function deleted(string $message = 'Resource deleted successfully'): array
    {
        return self::make(null, $message, 200);
    }

    /**
     * Paginated response
     *
     * @param mixed $data
     * @param string $message
     * @return array
     */
    public static function paginated($data, string $message = 'Data retrieved successfully'): array
    {
        return self::make($data, $message, 200);
    }
}
