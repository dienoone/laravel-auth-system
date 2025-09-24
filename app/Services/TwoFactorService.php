<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;

class TwoFactorService
{
  protected Google2FA $google2fa;

  public function __construct()
  {
    $this->google2fa = new Google2FA();
  }

  /**
   * Enable 2FA for user
   */
  public function enable(User $user): array
  {
    // Generate secret key
    $secretKey = $this->google2fa->generateSecretKey();

    // Generate recovery codes
    $recoveryCodes = $this->generateRecoveryCodes();

    // Store temporarily (not enabled until verified)
    $user->update([
      'two_factor_secret' => encrypt($secretKey),
      'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
    ]);

    return [
      'secret' => $secretKey,
      'qr_code' => $this->generateQrCode($user->email, $secretKey),
      'recovery_codes' => $recoveryCodes,
    ];
  }

  /**
   * Confirm 2FA setup
   */
  public function confirmEnable(User $user, string $code): bool
  {
    if (!$user->two_factor_secret) {
      throw new \Exception('2FA setup not initiated.');
    }

    $secret = decrypt($user->two_factor_secret);

    if ($this->verifyCode($secret, $code)) {
      $user->update(['two_factor_enabled' => true]);
      return true;
    }

    return false;
  }

  /**
   * Disable 2FA
   */
  public function disable(User $user, string $password): bool
  {
    // Verify password
    if (!password_verify($password, $user->password)) {
      throw new \Exception('Invalid password.');
    }

    $user->update([
      'two_factor_enabled' => false,
      'two_factor_secret' => null,
      'two_factor_recovery_codes' => null,
    ]);

    return true;
  }

  /**
   * Verify 2FA code
   */
  public function verifyCode(string $secret, string $code): bool
  {
    return $this->google2fa->verifyKey($secret, $code);
  }

  /**
   * Verify 2FA for user
   */
  public function verify(User $user, string $code): bool
  {
    if (!$user->two_factor_enabled || !$user->two_factor_secret) {
      return false;
    }

    $secret = decrypt($user->two_factor_secret);

    // First try to verify as TOTP code
    if ($this->verifyCode($secret, $code)) {
      return true;
    }

    // Then try recovery codes
    return $this->verifyRecoveryCode($user, $code);
  }

  /**
   * Verify recovery code
   */
  protected function verifyRecoveryCode(User $user, string $code): bool
  {
    if (!$user->two_factor_recovery_codes) {
      return false;
    }

    $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

    if (in_array($code, $recoveryCodes)) {
      // Remove used recovery code
      $recoveryCodes = array_diff($recoveryCodes, [$code]);

      $user->update([
        'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes)))
      ]);

      return true;
    }

    return false;
  }

  /**
   * Generate new recovery codes
   */
  public function regenerateRecoveryCodes(User $user): array
  {
    $recoveryCodes = $this->generateRecoveryCodes();

    $user->update([
      'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes))
    ]);

    return $recoveryCodes;
  }

  /**
   * Generate recovery codes
   */
  protected function generateRecoveryCodes(int $count = 8): array
  {
    $codes = [];

    for ($i = 0; $i < $count; $i++) {
      $codes[] = Str::random(10) . '-' . Str::random(10);
    }

    return $codes;
  }

  /**
   * Generate QR code
   */
  public function generateQrCode(string $email, string $secret): string
  {
    $appName = config('app.name', 'Laravel');
    $google2faUrl = $this->google2fa->getQRCodeUrl(
      $appName,
      $email,
      $secret
    );

    $renderer = new ImageRenderer(
      new RendererStyle(300),
      new SvgImageBackEnd()
    );

    $writer = new Writer($renderer);

    return $writer->writeString($google2faUrl);
  }

  /**
   * Get remaining recovery codes count
   */
  public function getRemainingRecoveryCodes(User $user): int
  {
    if (!$user->two_factor_recovery_codes) {
      return 0;
    }

    $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
    return count($recoveryCodes);
  }
}
