<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactor\VerifyTwoFactorRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Exception;

class LoginController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle user login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Rate limiting check
        $this->ensureIsNotRateLimited($request);

        try {
            // Get credentials
            $credentials = [
                'login' => $request->input('login'),
                'password' => $request->input('password'),
            ];

            // Attempt login
            $loginData = $this->authService->login(
                $credentials,
                $request->ip(),
                $request->input('device_name', $request->header('User-Agent', 'web'))
            );

            // Clear rate limiter on successful login
            RateLimiter::clear($this->throttleKey($request));

            if ($loginData['requires_two_factor']) {
                return $this->successResponse('Two-factor authentication required.', [
                    'requires_two_factor' => true,
                    'two_factor_token' => $loginData['two_factor_token'],
                ]);
            }

            return $this->successResponse('Login successful.', $loginData);
        } catch (ValidationException $e) {
            // Hit rate limiter on failed login
            RateLimiter::hit($this->throttleKey($request), 300); // 5 minutes

            throw $e;
        } catch (Exception $e) {
            Log::error('Login failed: ' . $e->getMessage(), [
                'login' => $request->input('login'),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Login failed. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Verify two-factor authentication
     */
    public function verifyTwoFactor(VerifyTwoFactorRequest $request): JsonResponse
    {
        try {
            // Verify the 2FA token
            $token = PersonalAccessToken::findToken($request->two_factor_token);

            if (!$token || !$token->can('2fa-verify')) {
                return $this->errorResponse('Invalid or expired two-factor token.');
            }

            $user = $token->tokenable;

            // Verify the 2FA code
            $authData = $this->authService->verifyTwoFactor(
                $user,
                $request->code,
                $request->ip(),
                $request->input('device_name', $request->header('User-Agent', 'web'))
            );

            // Delete the 2FA token
            $token->delete();

            return $this->successResponse('Login successful.', $authData);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('2FA verification failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Two-factor verification failed. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Ensure the login request is not rate limited
     */
    protected function ensureIsNotRateLimited(LoginRequest $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'login' => [
                trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ],
        ])->status(429);
    }

    /**
     * Get the rate limiting throttle key for the request
     */
    protected function throttleKey(LoginRequest $request): string
    {
        return strtolower($request->input('login')) . '|' . $request->ip();
    }
}
