<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class LogoutController extends Controller
{
    use ApiResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Logout current device
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user(), false);

            return $this->successResponse('Logged out successfully.');
        } catch (Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Logout failed. Please try again.',
                null,
                500
            );
        }
    }

    /**
     * Logout all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user(), true);

            return $this->successResponse('Logged out from all devices successfully.');
        } catch (Exception $e) {
            Log::error('Logout all devices failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Logout failed. Please try again.',
                null,
                500
            );
        }
    }
}
