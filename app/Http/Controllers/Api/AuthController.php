<?php
/**
 * AuthController
 * 
 * @author Achal Sharma
 * @package App\Http\Controllers\Api
 * @version 1.0.1
 */
namespace App\Http\Controllers\Api;

use App\Models\LoginLog;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\ResponseService;
use App\Http\Resources\AuthResource;
use App\Traits\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ValidatesRequests;

    /**
     * The auth service instance
     *
     * @var AuthService
     */
    protected $authService;

    /**
     * The response service instance
     *
     * @var ResponseService
     */
    protected $responseService;

    /**
     * Constructor
     *
     * @param AuthService $authService
     * @param ResponseService $responseService
     */
    public function __construct(AuthService $authService, ResponseService $responseService)
    {
        $this->authService = $authService;
        $this->responseService = $responseService;
    }

    /**
     * Register a new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->all());
            
            return $this->responseService->created(
                new AuthResource(
                    $result['user'],
                    $result['token'],
                    $result['token_type'],
                    $result['expires_in'],
                    $result['refresh_token'] ?? null
                ),
                'User registered successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Registration validation failed');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Login user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->all());

            // --- YAHAN LOGGING LOGIC ADD KAREIN ---
            if (isset($result['user'])) {
                /** @var \App\Models\User $user */
                $user = $result['user'];

                // (Optional) Purane "active" sessions ko logout mark karein
             $user->loginLogs()
                 ->whereNull('logout_time')
                 ->update(['logout_time' => \Carbon\Carbon::now()]);

                // Naya log banayein
                LoginLog::create([
                    'user_id' => $user->id,
                    'login_time' => \Carbon\Carbon::now(),
                    'login_data' => [
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                    ],
                ]);
            }
            // --- LOGGING LOGIC END ---
            
            return $this->responseService->success(
                new AuthResource(
                    $result['user'],
                    $result['token'],
                    $result['token_type'],
                    $result['expires_in'],
                    $result['refresh_token'] ?? null
                ),
                'Login successful'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Login validation failed');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = $request->user ?? Auth::user();

            if ($user) {
                $log = $user->loginLogs()
                           ->whereNull('logout_time')
                           ->latest('login_time')
                           ->first();

                if ($log) {
                    $log->update(['logout_time' => \Carbon\Carbon::now()]);
                }
            }

            $success = $this->authService->logout();
            
            if ($success) {
                return $this->responseService->success(null, 'Logout successful');
            }
            
            return $this->responseService->serverError('Logout failed');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Refresh token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->refresh($request);

            return $this->responseService->success(
                new AuthResource(
                    $result['user'],
                    $result['token'],
                    $result['token_type'],
                    $result['expires_in'],
                    $result['refresh_token'] ?? null
                ),
                'Token refreshed successfully'
            );
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Get current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                return $this->responseService->unauthorized('User not authenticated');
            }
            
            return $this->responseService->success(new \App\Http\Resources\UserResource($user), 'User profile retrieved successfully');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Forgot password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'email' => 'required|email'
            ]);

            $success = $this->authService->forgotPassword($request->email);
            
            if ($success) {
                return $this->responseService->success(null, 'Password reset email sent successfully');
            }
            
            return $this->responseService->notFound('User not found');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed'
            ]);

            $success = $this->authService->resetPassword($request->token, $request->password);
            
            if ($success) {
                return $this->responseService->success(null, 'Password reset successfully');
            }
            
            return $this->responseService->serverError('Password reset failed');
        } catch (ValidationException $e) {
            return $this->responseService->validationError($e->errors(), 'Validation failed');
        } catch (\Throwable $e) {
            return $this->responseService->handleException($e);
        }
    }
}