<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
  protected EmailVerificationService $emailVerificationService;

  public function __construct(EmailVerificationService $emailVerificationService)
  {
    $this->emailVerificationService = $emailVerificationService;
  }

  /**
   * Register a new user
   */
  public function register(array $data): User
  {
    return DB::transaction(function () use ($data) {
      // Create the user
      $user = User::create([
        'name' => $data['name'],
        'username' => $data['username'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'phone' => $data['phone'] ?? null,
        'email_verified' => false,
      ]);

      // Assign default role
      $userRole = Role::where('slug', 'user')->first();
      if ($userRole) {
        $user->roles()->attach($userRole);
      }

      // Send email verification
      $this->emailVerificationService->sendVerificationEmail($user);

      return $user;
    });
  }

  /**
   * Handle user login
   */
  public function login(array $credentials, string $ipAddress, string $deviceName = 'web'): array
  {
    // Find user by email or username
    $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    $user = User::where($loginField, $credentials['login'])->first();

    // Check if user exists
    if (!$user) {
      throw ValidationException::withMessages([
        'login' => ['The provided credentials are incorrect.'],
      ]);
    }

    // Check if account is locked
    if ($user->isLocked()) {
      $lockTime = $user->locked_until->diffForHumans();
      throw ValidationException::withMessages([
        'login' => ["Your account is locked. Please try again {$lockTime}."],
      ]);
    }

    // Check if account is active
    if (!$user->is_active) {
      throw ValidationException::withMessages([
        'login' => ['Your account has been deactivated. Please contact support.'],
      ]);
    }

    // Verify password
    if (!Hash::check($credentials['password'], $user->password)) {
      $user->incrementFailedAttempts();

      throw ValidationException::withMessages([
        'login' => ['The provided credentials are incorrect.'],
      ]);
    }

    // Reset failed attempts
    $user->resetFailedAttempts();

    // Check if 2FA is enabled
    if ($user->two_factor_enabled) {
      // Return partial auth data for 2FA verification
      return [
        'requires_two_factor' => true,
        'two_factor_token' => $this->createTwoFactorToken($user),
        'user_id' => $user->id,
      ];
    }

    // Update login info
    $user->updateLastLogin($ipAddress);

    // Create token
    $token = $this->createToken($user, $deviceName);

    return [
      'requires_two_factor' => false,
      'user' => $this->getUserData($user),
      'token' => $token,
      'token_type' => 'Bearer'
    ];
  }

  /**
   * Create temporary token for 2FA verification
   */
  protected function createTwoFactorToken(User $user): string
  {
    return $user->createToken('2fa-verification', ['2fa-verify'], now()->addMinutes(10))->plainTextToken;
  }

  /**
   * Complete login with 2FA
   */
  public function verifyTwoFactor(User $user, string $code, string $ipAddress, string $deviceName = 'web'): array
  {
    $twoFactorService = app(TwoFactorService::class);

    if (!$twoFactorService->verify($user, $code)) {
      throw ValidationException::withMessages([
        'code' => ['Invalid two-factor authentication code.'],
      ]);
    }

    // Update login info
    $user->updateLastLogin($ipAddress);

    // Create token
    $token = $this->createToken($user, $deviceName);

    return [
      'user' => $this->getUserData($user),
      'token' => $token,
      'token_type' => 'Bearer'
    ];
  }

  /**
   * Logout user
   */
  public function logout(User $user, bool $allDevices = false): void
  {
    if ($allDevices) {
      // Revoke all tokens
      $user->tokens()->delete();
    } else {
      // Revoke current token
      $user->currentAccessToken()->delete();
    }
  }

  /**
   * Send email verification
   */
  public function sendEmailVerification(User $user): void
  {
    $this->emailVerificationService->sendVerificationEmail($user);
  }

  /**
   * Create authentication token
   */
  public function createToken(User $user, string $deviceName = 'web'): string
  {
    // Delete old tokens for this device
    $user->tokens()->where('name', $deviceName)->delete();

    // Create new token with abilities based on permissions
    $abilities = $user->getAllPermissions()->pluck('slug')->toArray();

    return $user->createToken($deviceName, $abilities)->plainTextToken;
  }

  /**
   * Get user data for response
   */
  public function getUserData(User $user): array
  {
    return [
      'id' => $user->id,
      'name' => $user->name,
      'username' => $user->username,
      'email' => $user->email,
      'avatar' => $user->avatar,
      'phone' => $user->phone,
      'email_verified' => $user->email_verified,
      'two_factor_enabled' => $user->two_factor_enabled,
      'roles' => $user->roles->map(function ($role) {
        return [
          'id' => $role->id,
          'name' => $role->name,
          'slug' => $role->slug
        ];
      }),
      'permissions' => $user->getAllPermissions()->map(function ($permission) {
        return [
          'id' => $permission->id,
          'name' => $permission->name,
          'slug' => $permission->slug
        ];
      })
    ];
  }
}
