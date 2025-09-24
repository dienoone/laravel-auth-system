<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LinkSocialAccountRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\SocialAuthService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    use ApiResponseTrait;

    protected SocialAuthService $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Redirect to provider
     */
    public function redirect(Request $request, string $provider): JsonResponse
    {
        try {
            // Check if provider is configured
            if (!$this->socialAuthService->isProviderConfigured($provider)) {
                return $this->errorResponse(
                    ucfirst($provider) . ' authentication is not configured.',
                    null,
                    503
                );
            }

            $redirectUrl = $this->socialAuthService->getRedirectUrl($provider);

            return $this->successResponse('Redirect URL generated.', [
                'redirect_url' => $redirectUrl,
                'provider' => $provider
            ]);
        } catch (\Exception $e) {
            Log::error('Social auth redirect failed: ' . $e->getMessage(), [
                'provider' => $provider,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle provider callback
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        try {
            // For mobile apps or SPAs, they might send the code directly
            $code = $request->input('code');

            $authData = $this->socialAuthService->handleCallback($provider, $code);

            $message = $authData['is_new_user']
                ? 'Account created successfully.'
                : 'Login successful.';

            unset($authData['is_new_user']);

            return $this->successResponse($message, $authData);
        } catch (\Exception $e) {
            Log::error('Social auth callback failed: ' . $e->getMessage(), [
                'provider' => $provider,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Handle mobile app social login
     */
    public function mobile(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        try {
            // This method is for mobile apps that get the token directly
            $authData = $this->socialAuthService->handleMobileAuth(
                $provider,
                $request->input('access_token')
            );

            $message = $authData['is_new_user']
                ? 'Account created successfully.'
                : 'Login successful.';

            unset($authData['is_new_user']);

            return $this->successResponse($message, $authData);
        } catch (\Exception $e) {
            Log::error('Mobile social auth failed: ' . $e->getMessage(), [
                'provider' => $provider,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Unlink social account
     */
    public function unlink(Request $request, string $provider): JsonResponse
    {
        try {
            $user = $this->socialAuthService->unlinkProvider($request->user(), $provider);

            return $this->successResponse(
                ucfirst($provider) . ' account unlinked successfully.',
                ['user' => $user]
            );
        } catch (\Exception $e) {
            Log::error('Failed to unlink social account: ' . $e->getMessage(), [
                'provider' => $provider,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Get linked providers
     */
    public function linkedProviders(Request $request): JsonResponse
    {
        try {
            $providers = $this->socialAuthService->getLinkedProviders($request->user());

            $allProviders = ['google', 'github', 'facebook'];
            $providerStatus = [];

            foreach ($allProviders as $provider) {
                $linked = collect($providers)->firstWhere('provider', $provider);
                $providerStatus[$provider] = [
                    'linked' => !is_null($linked),
                    'configured' => $this->socialAuthService->isProviderConfigured($provider),
                    'linked_at' => $linked['linked_at'] ?? null
                ];
            }

            return $this->successResponse('Linked providers retrieved.', [
                'providers' => $providerStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get linked providers: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve linked providers.');
        }
    }

    /**
     * Link social account to existing user
     */
    public function link(LinkSocialAccountRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $provider = $request->input('provider');

            // Check if user already has a provider linked
            if ($user->provider) {
                return $this->errorResponse(
                    'You already have a ' . $user->provider . ' account linked. Please unlink it first.'
                );
            }

            // Get social user data
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($request->input('access_token'));

            // Check if this social account is already linked to another user
            $existingUser = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                return $this->errorResponse(
                    'This ' . ucfirst($provider) . ' account is already linked to another user.'
                );
            }

            // Link the account
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
                'avatar' => $user->avatar ?? $socialUser->getAvatar(),
            ]);

            return $this->successResponse(
                ucfirst($provider) . ' account linked successfully.',
                ['user' => $this->socialAuthService->authService->getUserData($user)]
            );
        } catch (\Exception $e) {
            Log::error('Failed to link social account: ' . $e->getMessage(), [
                'provider' => $request->input('provider'),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to link social account: ' . $e->getMessage());
        }
    }
}
