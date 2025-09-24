<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class PasswordController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Update password for authenticated user
     */
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Update password
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Logout from all other devices for security
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

            return $this->successResponse(
                'Password updated successfully. You have been logged out from all other devices.',
                ['user' => $this->authService->getUserData($user)]
            );
        } catch (Exception $e) {
            Log::error('Failed to update password: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to update password. Please try again.',
                null,
                500
            );
        }
    }
}
