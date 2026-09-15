<?php

namespace App\Services;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthService
{
    /**
     * The user service instance
     *
     * @var UserService
     */
    protected $userService;

    /**
     * Constructor
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Register a new user
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function register(array $data): array
    {
        $this->validateRegistrationData($data);
       
        // Create user
        $user = $this->userService->createUser($data);

        // Generate token
        $token = $user->generateToken();

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ];
    }

    /**
     * Login user
     *
     * @param array $credentials
     * @return array
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        try {
            $this->validateLoginData($credentials);
    
            $user = $this->userService->authenticateUser($credentials);
        
            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.']
                ]);
            }

            // Generate token
            $token = $user->generateToken();
        
            
            // Generate refresh token
            $refreshToken = Str::random(64);
            
            /**
             * Updated authenticated user loading to include departments along
             * with roles and organisation relationships in authentication responses.
             */
            // Save refresh token to database
            $user->refresh_token = $refreshToken;
            $user->save();

            $user->load(['roles', 'organisations', 'organisation', 'departments']);
    
            return [
                'user' => $user,
                'token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ];
            
        } catch (ValidationException $e) {
            throw new ValidationException($e->validator, 'Invalid login data');
        }
    }

    /**
     * Logout user
     *
     * @return bool
     */
    public function logout(): bool
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if ($user) {
                $user->refresh_token = null;
                $user->save();
            }

            JWTAuth::invalidate(JWTAuth::getToken());

            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Refresh access token using refresh_token (body) or expired JWT (Authorization header).
     *
     * @param Request $request
     * @return array
     * @throws JWTException|ValidationException
     */
    public function refresh(Request $request): array
    {
        $refreshToken = $this->resolveRefreshTokenFromRequest($request);

        if ($refreshToken !== null) {
            return $this->refreshWithDatabaseToken($refreshToken);
        }

        $bearer = $request->bearerToken();
        if (!empty($bearer)) {
            return $this->refreshWithJwt($request);
        }

        throw new JWTException('Refresh token is required');
    }

    /**
     * Read refresh_token from JSON body, form body, or query (supports refreshToken camelCase).
     */
    protected function resolveRefreshTokenFromRequest(Request $request): ?string
    {
        $candidates = [
            $request->input('refresh_token'),
            $request->input('refreshToken'),
            $request->query('refresh_token'),
            $request->query('refreshToken'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $content = $request->getContent();
        if (is_string($content) && $content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                foreach (['refresh_token', 'refreshToken'] as $key) {
                    if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                        return trim($decoded[$key]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Issue new tokens using the refresh_token stored on the user record.
     */
    protected function refreshWithDatabaseToken(string $refreshToken): array
    {
        $user = User::where('refresh_token', $refreshToken)->first();

        if (!$user) {
            throw new JWTException('Invalid or expired refresh token');
        }

        if (!$user->isActive()) {
            throw new JWTException('User account is inactive');
        }

        return $this->issueTokenPair($user);
    }

    /**
     * Issue new tokens by refreshing an existing JWT (including expired within refresh TTL).
     */
    protected function refreshWithJwt(Request $request): array
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                throw new JWTException('Invalid or expired access token');
            }

            if (!$user->isActive()) {
                throw new JWTException('User account is inactive');
            }

            $token = JWTAuth::refresh(JWTAuth::getToken());

            return $this->rotateRefreshTokenAndReturn($user, $token);
        } catch (TokenExpiredException $e) {
            try {
                $payload = JWTAuth::parseToken()->getPayload();
                $userId = $payload->get('sub');
                $user = $this->userService->getUserById((int) $userId);

                if (!$user || !$user->isActive()) {
                    throw new JWTException('Invalid or expired access token');
                }

                $token = JWTAuth::refresh(JWTAuth::getToken());

                return $this->rotateRefreshTokenAndReturn($user, $token);
            } catch (\Exception $inner) {
                throw new JWTException('Invalid or expired access token');
            }
        } catch (JWTException $e) {
            throw new JWTException($e->getMessage() ?: 'Invalid or expired access token');
        }
    }

    /**
     * Generate JWT + new refresh_token for a user.
     */
    protected function issueTokenPair(User $user): array
    {
        $token = $user->generateToken();

        if (!is_string($token) || str_contains($token, 'Token')) {
            throw new JWTException('Unable to generate access token');
        }

        return $this->rotateRefreshTokenAndReturn($user, $token);
    }

    /**
     * Persist a new refresh_token and build the auth response payload.
     */
    protected function rotateRefreshTokenAndReturn(User $user, string $accessToken): array
    {
        $refreshToken = Str::random(64);
        $user->refresh_token = $refreshToken;
        $user->save();

        $user->load(['roles', 'organisations', 'organisation', 'departments']);

        return [
            'user' => $user,
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }

    /**
     * Get current authenticated user
     *
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            // Eager load roles to include role_id and role_name in response
            if ($user) {
                $user->load(['roles', 'organisations', 'organisation', 'departments']);
            }
            return $user;
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        try {
            JWTAuth::parseToken()->authenticate();
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Forgot password
     *
     * @param string $email
     * @return bool
     */
    public function forgotPassword(string $email): bool
    {
        // This would typically send an email with reset link
        // For now, we'll just return true if user exists
        $user = $this->userService->getUserByEmail($email);
        return $user !== null;
    }

    /**
     * Reset password
     *
     * @param string $token
     * @param string $password
     * @return bool
     * @throws ValidationException
     */
    public function resetPassword(string $token, string $password): bool
    {
        // This would typically validate the reset token
        // Password confirmation is validated in the controller
        // For now, we'll just validate the password format
        $validator = Validator::make(['password' => $password], [
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // In a real implementation, you would:
        // 1. Validate the reset token
        // 2. Find the user associated with the token
        // 3. Update their password
        // 4. Invalidate the token

        return true;
    }

    /**
     * Validate registration data
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateRegistrationData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|integer|digits:10',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate login data
     *
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateLoginData(array $data): void
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}