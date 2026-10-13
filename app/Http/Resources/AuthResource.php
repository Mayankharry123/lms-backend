<?php
/**
 * AuthResource
 * 
 * @author Achal Sharma
 * @package App\Http\Resources
 * @version 1.0.1
 */
namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthResource extends BaseResource
{
    /**
     * The user resource instance
     *
     * @var UserResource
     */
    protected $userResource;

    /**
     * The token
     *
     * @var string
     */
    protected $token;

    /**
     * The token type
     *
     * @var string
     */
    protected $tokenType;

    /**
     * The token expiration time
     *
     * @var int
     */
    protected $expiresIn;

    /**
     * The refresh token
     *
     * @var string|null
     */
    protected $refreshToken;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @param  string  $token
     * @param  string  $tokenType
     * @param  int  $expiresIn
     * @param  string|null  $refreshToken
     * @return void
     */
    public function __construct($resource, string $token, string $tokenType = 'bearer', int $expiresIn = 3600, ?string $refreshToken = null)
    {
        parent::__construct($resource);
        $this->token = $token;
        $this->refreshToken = $refreshToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->userResource = new UserResource($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = [
            // 'user' => $this->userResource->toArray($request),
            'token' => $this->token,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            
        ];
        
        // Include refresh token if available
        // if ($this->refreshToken !== null) {
        //     $data['refresh_token'] = $this->refreshToken;
        // }
        
        return $data;
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'timestamp' => Carbon::now()->toISOString(),
                'message' => 'Authentication successful',
            ]
        ];
    }
}