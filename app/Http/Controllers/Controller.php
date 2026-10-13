<?php
/**
 * Controller
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers
 * @version 1.0.1
 */
namespace App\Http\Controllers;

use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Routing\Controller as BaseController;
use Throwable;

class Controller extends BaseController
{
    /**
     * Map any throwable to the standard API error JSON (same shape as global Handler).
     */
    protected function apiExceptionResponse(Throwable $exception, ResponseService $responses): JsonResponse
    {
        return $responses->handleException($exception);
    }

    /**
     * Common controller try/catch pattern preserving existing response format.
     */
    protected function handleApiAction(callable $action, ResponseService $responses): JsonResponse
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            return $responses->validationError($e->errors(), 'Validation failed');
        } catch (Throwable $e) {
            return $this->apiExceptionResponse($e, $responses);
        }
    }
}
