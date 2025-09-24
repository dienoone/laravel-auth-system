<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class RegisterController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user registration
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Register the user
            $user = $this->authService->register($request->validated());

            // Create authentication token
            $token = $this->authService->createToken($user, $request->header('User-Agent', 'web'));

            // Get user data
            $userData = $this->authService->getUserData($user);

            return $this->successResponse(
                'Registration successful. Please check your email for verification.',
                [
                    'user' => $userData,
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                201
            );
        } catch (Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Registration failed. Please try again.',
                null,
                500
            );
        }
    }
}
