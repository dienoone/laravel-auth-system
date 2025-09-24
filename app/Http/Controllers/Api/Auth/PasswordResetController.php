<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordResetService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class PasswordResetController extends Controller
{
    use ApiResponseTrait;

    protected PasswordResetService $passwordResetService;
    protected AuthService $authService;

    public function __construct(
        PasswordResetService $passwordResetService,
        AuthService $authService
    ) {
        $this->passwordResetService = $passwordResetService;
        $this->authService = $authService;
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $email = $request->validated()['email'];

            // Check if user can request reset
            if (!$this->passwordResetService->canRequestReset($email)) {
                return $this->errorResponse(
                    'Please wait a moment before requesting another password reset.',
                    null,
                    429
                );
            }

            // Send reset link
            $this->passwordResetService->sendResetLink($email);

            return $this->successResponse(
                'If an account exists with this email, a password reset link has been sent.'
            );
        } catch (Exception $e) {
            Log::error('Failed to send password reset: ' . $e->getMessage(), [
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to process password reset request. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Validate reset token
     */
    public function validateToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $resetToken = $this->passwordResetService->validateResetToken($request->token);

            return $this->successResponse('Token is valid.', [
                'email' => $resetToken->email,
                'expires_at' => $resetToken->expires_at,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to validate reset token: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to validate token. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Reset password
            $user = $this->passwordResetService->resetPassword(
                $data['token'],
                $data['password']
            );

            // Create new token for auto-login
            $token = $this->authService->createToken($user, $request->header('User-Agent', 'web'));

            return $this->successResponse(
                'Password reset successfully. You have been logged in.',
                [
                    'user' => $this->authService->getUserData($user),
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to reset password. Please try again.',
                null,
                500
            );
        }
    }
}
