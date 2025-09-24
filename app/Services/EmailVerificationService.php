<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
  /**
   * Send verification email
   */
  public function sendVerificationEmail(User $user): void
  {
    // Delete existing tokens for this user
    EmailVerificationToken::where('user_id', $user->id)->delete();

    // Create new verification token
    $token = $this->createVerificationToken($user);

    // Send notification
    $user->notify(new VerifyEmailNotification($token));
  }

  /**
   * Create verification token
   */
  protected function createVerificationToken(User $user): EmailVerificationToken
  {
    return EmailVerificationToken::create([
      'user_id' => $user->id,
      'email' => $user->email,
      'token' => Str::random(64),
      'expires_at' => now()->addHours(24), // Token expires in 24 hours
    ]);
  }

  /**
   * Verify email with token
   */
  public function verifyEmail(string $token): User
  {
    $verificationToken = EmailVerificationToken::where('token', $token)
      ->with('user')
      ->first();

    if (!$verificationToken) {
      throw ValidationException::withMessages([
        'token' => ['Invalid verification token.'],
      ]);
    }

    if ($verificationToken->isExpired()) {
      throw ValidationException::withMessages([
        'token' => ['Verification token has expired. Please request a new one.'],
      ]);
    }

    $user = $verificationToken->user;

    // Check if email matches
    if ($user->email !== $verificationToken->email) {
      throw ValidationException::withMessages([
        'token' => ['This verification token is no longer valid.'],
      ]);
    }

    // Mark email as verified
    $user->update([
      'email_verified' => true,
      'email_verified_at' => now(),
    ]);

    // Delete the token
    $verificationToken->delete();

    // Delete any other pending tokens for this user
    EmailVerificationToken::where('user_id', $user->id)->delete();

    return $user;
  }

  /**
   * Check if user can resend verification email
   */
  public function canResendVerification(User $user): bool
  {
    $lastToken = EmailVerificationToken::where('user_id', $user->id)
      ->latest()
      ->first();

    if (!$lastToken) {
      return true;
    }

    // Allow resend after 60 seconds
    return $lastToken->created_at->diffInSeconds(now()) > 60;
  }
}
