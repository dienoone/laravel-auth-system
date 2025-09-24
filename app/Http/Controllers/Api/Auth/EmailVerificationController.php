<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class EmailVerificationController extends Controller
{
    use ApiResponseTrait;

    protected EmailVerificationService $verificationService;
    protected AuthService $authService;

    public function __construct(
        EmailVerificationService $verificationService,
        AuthService $authService
    ) {
        $this->verificationService = $verificationService;
        $this->authService = $authService;
    }

    /**
     * Verify email address
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $user = $this->verificationService->verifyEmail($request->token);

            return $this->successResponse(
                'Email verified successfully.',
                ['user' => $this->authService->getUserData($user)]
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Email verification failed: ' . $e->getMessage(), [
                'token' => $request->token,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Email verification failed. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if email is already verified
            if ($user->email_verified) {
                return $this->errorResponse('Email is already verified.');
            }

            // Check if user can resend
            if (!$this->verificationService->canResendVerification($user)) {
                return $this->errorResponse(
                    'Please wait a moment before requesting another verification email.',
                    null,
                    429
                );
            }

            // Send verification email
            $this->verificationService->sendVerificationEmail($user);

            return $this->successResponse(
                'Verification email sent successfully. Please check your inbox.'
            );
        } catch (Exception $e) {
            Log::error('Failed to resend verification email: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to send verification email. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Get verification status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse('Verification status retrieved.', [
            'email_verified' => $user->email_verified,
            'email_verified_at' => $user->email_verified_at,
            'email' => $user->email
        ]);
    }
}
