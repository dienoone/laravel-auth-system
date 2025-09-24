<?php

namespace App\Services;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class PasswordResetService
{
  /**
   * Send password reset link
   */
  public function sendResetLink(string $email): void
  {
    $user = User::where('email', $email)->first();

    if (!$user) {
      // Don't reveal if email exists or not
      return;
    }

    // Delete existing tokens for this email
    PasswordResetToken::where('email', $email)->delete();

    // Create new reset token
    $token = $this->createResetToken($email);

    // Send notification
    $user->notify(new ResetPasswordNotification($token));
  }

  /**
   * Create password reset token
   */
  protected function createResetToken(string $email): PasswordResetToken
  {
    return PasswordResetToken::create([
      'email' => $email,
      'token' => Str::random(64),
      'expires_at' => now()->addHours(1), // Token expires in 1 hour
    ]);
  }

  /**
   * Validate reset token
   */
  public function validateResetToken(string $token): PasswordResetToken
  {
    $resetToken = PasswordResetToken::where('token', $token)->first();

    if (!$resetToken) {
      throw ValidationException::withMessages([
        'token' => ['Invalid password reset token.'],
      ]);
    }

    if ($resetToken->isExpired()) {
      throw ValidationException::withMessages([
        'token' => ['Password reset token has expired. Please request a new one.'],
      ]);
    }

    return $resetToken;
  }

  /**
   * Reset password with token
   */
  public function resetPassword(string $token, string $password): User
  {
    $resetToken = $this->validateResetToken($token);

    $user = User::where('email', $resetToken->email)->first();

    if (!$user) {
      throw ValidationException::withMessages([
        'email' => ['User not found.'],
      ]);
    }

    // Update password
    $user->update([
      'password' => Hash::make($password),
    ]);

    // Delete all reset tokens for this email
    PasswordResetToken::where('email', $resetToken->email)->delete();

    // Logout from all devices for security
    $user->tokens()->delete();

    return $user;
  }

  /**
   * Check if user can request reset
   */
  public function canRequestReset(string $email): bool
  {
    $lastToken = PasswordResetToken::where('email', $email)
      ->latest()
      ->first();

    if (!$lastToken) {
      return true;
    }

    // Allow new request after 60 seconds
    return $lastToken->created_at->diffInSeconds(now()) > 60;
  }

  /**
   * Get password validation rules
   */
  public function getPasswordRules(): Password
  {
    $rules = Password::min(8)
      ->mixedCase()
      ->numbers()
      ->symbols();

    if (app()->environment('production')) {
      $rules->uncompromised();
    }

    return $rules;
  }
}
