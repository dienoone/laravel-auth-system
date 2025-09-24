<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthService
{
  public AuthService $authService;

  public function __construct(AuthService $authService)
  {
    $this->authService = $authService;
  }

  /**
   * Get the redirect URL for the provider
   */
  public function getRedirectUrl(string $provider): string
  {
    $this->validateProvider($provider);

    return Socialite::driver($provider)
      ->stateless() // For API usage
      ->redirect()
      ->getTargetUrl();
  }

  /**
   * Handle the callback from the provider
   */
  public function handleCallback(string $provider, ?string $code = null): array
  {
    $this->validateProvider($provider);

    try {
      // Get social user
      $socialUser = $code
        ? Socialite::driver($provider)->stateless()->userFromCode($code)
        : Socialite::driver($provider)->stateless()->user();

      // Find or create user
      $user = $this->findOrCreateUser($socialUser, $provider);

      // Create token
      $token = $this->authService->createToken($user, 'social-' . $provider);

      return [
        'user' => $this->authService->getUserData($user),
        'token' => $token,
        'token_type' => 'Bearer',
        'is_new_user' => $user->wasRecentlyCreated
      ];
    } catch (\Exception $e) {
      throw new \Exception('Failed to authenticate with ' . ucfirst($provider) . ': ' . $e->getMessage());
    }
  }

  /**
   * Find or create user from social provider
   */
  protected function findOrCreateUser(SocialiteUser $socialUser, string $provider): User
  {
    return DB::transaction(function () use ($socialUser, $provider) {
      // First, try to find user by provider ID
      $user = User::where('provider', $provider)
        ->where('provider_id', $socialUser->getId())
        ->first();

      if ($user) {
        // Update provider token
        $user->update([
          'provider_token' => $socialUser->token,
          'last_login_at' => now(),
        ]);
        return $user;
      }

      // Try to find user by email
      $user = User::where('email', $socialUser->getEmail())->first();

      if ($user) {
        // Link social account to existing user
        $user->update([
          'provider' => $provider,
          'provider_id' => $socialUser->getId(),
          'provider_token' => $socialUser->token,
          'email_verified' => true,
          'email_verified_at' => $user->email_verified_at ?? now(),
        ]);
        return $user;
      }

      // Create new user
      return $this->createUserFromSocialProvider($socialUser, $provider);
    });
  }

  /**
   * Create a new user from social provider data
   */
  protected function createUserFromSocialProvider(SocialiteUser $socialUser, string $provider): User
  {
    $user = User::create([
      'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
      'email' => $socialUser->getEmail(),
      'username' => $this->generateUniqueUsername($socialUser, $provider),
      'avatar' => $socialUser->getAvatar(),
      'password' => Hash::make(Str::random(32)), // Random password for social users
      'provider' => $provider,
      'provider_id' => $socialUser->getId(),
      'provider_token' => $socialUser->token,
      'email_verified' => true,
      'email_verified_at' => now(),
    ]);

    // Assign default role
    $userRole = Role::where('slug', 'user')->first();
    if ($userRole) {
      $user->roles()->attach($userRole);
    }

    return $user;
  }

  /**
   * Generate unique username from social data
   */
  protected function generateUniqueUsername(SocialiteUser $socialUser, string $provider): string
  {
    $baseUsername = $socialUser->getNickname()
      ?? Str::slug($socialUser->getName())
      ?? $provider . '_user';

    $username = $baseUsername;
    $counter = 1;

    while (User::where('username', $username)->exists()) {
      $username = $baseUsername . $counter;
      $counter++;
    }

    return $username;
  }

  /**
   * Unlink social account
   */
  public function unlinkProvider(User $user, string $provider): User
  {
    $this->validateProvider($provider);

    if ($user->provider !== $provider) {
      throw new \Exception('This account is not linked with ' . ucfirst($provider));
    }

    // Check if user has a password set
    if (!$user->password) {
      throw new \Exception('Please set a password before unlinking your social account');
    }

    $user->update([
      'provider' => null,
      'provider_id' => null,
      'provider_token' => null,
    ]);

    return $user;
  }

  /**
   * Get linked providers for user
   */
  public function getLinkedProviders(User $user): array
  {
    $providers = ['google', 'github', 'facebook'];
    $linked = [];

    foreach ($providers as $provider) {
      if ($user->provider === $provider) {
        $linked[] = [
          'provider' => $provider,
          'linked' => true,
          'linked_at' => $user->updated_at,
        ];
      }
    }

    return $linked;
  }

  /**
   * Validate provider name
   */
  protected function validateProvider(string $provider): void
  {
    $allowedProviders = ['google', 'github', 'facebook'];

    if (!in_array($provider, $allowedProviders)) {
      throw new \Exception('Invalid provider. Allowed providers: ' . implode(', ', $allowedProviders));
    }
  }

  /**
   * Check if provider is configured
   */
  public function isProviderConfigured(string $provider): bool
  {
    $this->validateProvider($provider);

    $clientId = config("services.{$provider}.client_id");
    $clientSecret = config("services.{$provider}.client_secret");

    return !empty($clientId) && !empty($clientSecret);
  }

  /**
   * Handle mobile app authentication
   */
  public function handleMobileAuth(string $provider, string $accessToken): array
  {
    $this->validateProvider($provider);

    try {
      // Get user details from provider using access token
      $socialUser = Socialite::driver($provider)
        ->stateless()
        ->userFromToken($accessToken);

      // Find or create user
      $user = $this->findOrCreateUser($socialUser, $provider);

      // Create token
      $token = $this->authService->createToken($user, 'mobile-' . $provider);

      return [
        'user' => $this->authService->getUserData($user),
        'token' => $token,
        'token_type' => 'Bearer',
        'is_new_user' => $user->wasRecentlyCreated
      ];
    } catch (\Exception $e) {
      throw new \Exception('Failed to authenticate with ' . ucfirst($provider) . ': ' . $e->getMessage());
    }
  }
}
