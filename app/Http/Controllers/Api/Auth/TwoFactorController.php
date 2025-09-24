<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactor\EnableTwoFactorRequest;
use App\Http\Requests\Auth\TwoFactor\ConfirmTwoFactorRequest;
use App\Http\Requests\Auth\TwoFactor\DisableTwoFactorRequest;
use App\Services\TwoFactorService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwoFactorController extends Controller
{
    use ApiResponseTrait;

    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Get 2FA status
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse('2FA status retrieved.', [
            'enabled' => $user->two_factor_enabled,
            'recovery_codes_remaining' => $this->twoFactorService->getRemainingRecoveryCodes($user),
        ]);
    }

    /**
     * Enable 2FA - Step 1: Generate secret
     */
    public function enable(EnableTwoFactorRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->two_factor_enabled) {
                return $this->errorResponse('Two-factor authentication is already enabled.');
            }

            $setupData = $this->twoFactorService->enable($user);

            return $this->successResponse(
                'Two-factor authentication setup initiated. Please scan the QR code with your authenticator app.',
                [
                    'qr_code' => $setupData['qr_code'],
                    'secret' => $setupData['secret'],
                    'recovery_codes' => $setupData['recovery_codes'],
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to enable 2FA: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to enable two-factor authentication.');
        }
    }

    /**
     * Enable 2FA - Step 2: Confirm with code
     */
    public function confirmEnable(ConfirmTwoFactorRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->two_factor_enabled) {
                return $this->errorResponse('Two-factor authentication is already enabled.');
            }

            $confirmed = $this->twoFactorService->confirmEnable($user, $request->code);

            if (!$confirmed) {
                return $this->errorResponse('Invalid verification code. Please try again.');
            }

            return $this->successResponse(
                'Two-factor authentication enabled successfully. Please save your recovery codes in a safe place.'
            );
        } catch (\Exception $e) {
            Log::error('Failed to confirm 2FA: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Disable 2FA
     */
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        try {
            $this->twoFactorService->disable($request->user(), $request->password);

            return $this->successResponse('Two-factor authentication disabled successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to disable 2FA: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        try {
            $user = $request->user();

            if (!$user->two_factor_enabled) {
                return $this->errorResponse('Two-factor authentication is not enabled.');
            }

            $recoveryCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

            return $this->successResponse(
                'Recovery codes regenerated successfully. Please save these new codes.',
                ['recovery_codes' => $recoveryCodes]
            );
        } catch (\Exception $e) {
            Log::error('Failed to regenerate recovery codes: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to regenerate recovery codes.');
        }
    }
}
