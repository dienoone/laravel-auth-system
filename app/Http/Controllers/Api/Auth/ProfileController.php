<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    protected ProfileService $profileService;
    protected AuthService $authService;

    public function __construct(ProfileService $profileService, AuthService $authService)
    {
        $this->profileService = $profileService;
        $this->authService = $authService;
    }

    /**
     * Get current user profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $profileData = $this->profileService->getProfileWithStats($request->user());

            return $this->successResponse('Profile retrieved successfully.', $profileData);
        } catch (Exception $e) {
            Log::error('Failed to retrieve profile: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to retrieve profile.',
                null,
                500
            );
        }
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->profileService->updateProfile(
                $request->user(),
                $request->validated()
            );

            // Check if email was updated
            if ($request->has('email') && $request->email !== $request->user()->email) {
                $user->update(['email_verified' => false]);
                $this->authService->sendEmailVerification($user);

                $message = 'Profile updated successfully. Please verify your new email address.';
            } else {
                $message = 'Profile updated successfully.';
            }

            $profileData = $this->profileService->getProfileWithStats($user);

            return $this->successResponse($message, $profileData);
        } catch (Exception $e) {
            Log::error('Failed to update profile: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to update profile.',
                null,
                500
            );
        }
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            $user = $this->profileService->updateProfile(
                $request->user(),
                ['remove_avatar' => true]
            );

            $profileData = $this->profileService->getProfileWithStats($user);

            return $this->successResponse('Avatar removed successfully.', $profileData);
        } catch (Exception $e) {
            Log::error('Failed to delete avatar: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to remove avatar.',
                null,
                500
            );
        }
    }
}
