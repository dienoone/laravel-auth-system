<?php

namespace App\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RateLimitService
{
  public RateLimiter $limiter;

  public function __construct(RateLimiter $limiter)
  {
    $this->limiter = $limiter;
  }

  /**
   * Get rate limit key for login attempts
   */
  public function getLoginKey(string $identifier, string $ip): string
  {
    return 'login_attempts:' . sha1(strtolower($identifier) . '|' . $ip);
  }

  /**
   * Get rate limit key for IP-based limiting
   */
  public function getIpKey(string $ip, string $action = 'general'): string
  {
    return "ip_limit:{$action}:" . sha1($ip);
  }

  /**
   * Get rate limit key for user-based limiting
   */
  public function getUserKey(int $userId, string $action = 'general'): string
  {
    return "user_limit:{$action}:{$userId}";
  }

  /**
   * Check if login is rate limited
   */
  public function isLoginLimited(string $identifier, string $ip): bool
  {
    $key = $this->getLoginKey($identifier, $ip);
    return $this->limiter->tooManyAttempts($key, $this->getLoginMaxAttempts());
  }

  /**
   * Hit login rate limiter
   */
  public function hitLogin(string $identifier, string $ip): void
  {
    $key = $this->getLoginKey($identifier, $ip);
    $this->limiter->hit($key, $this->getLoginDecayMinutes() * 60);
  }

  /**
   * Clear login rate limiter
   */
  public function clearLogin(string $identifier, string $ip): void
  {
    $key = $this->getLoginKey($identifier, $ip);
    $this->limiter->clear($key);
  }

  /**
   * Get remaining seconds until login is available
   */
  public function getLoginAvailableIn(string $identifier, string $ip): int
  {
    $key = $this->getLoginKey($identifier, $ip);
    return $this->limiter->availableIn($key);
  }

  /**
   * Check if IP is globally rate limited
   */
  public function isIpBlocked(string $ip): bool
  {
    return Cache::has("blocked_ip:{$ip}");
  }

  /**
   * Block IP address
   */
  public function blockIp(string $ip, int $minutes = 60, string $reason = 'Suspicious activity'): void
  {
    Cache::put("blocked_ip:{$ip}", [
      'blocked_at' => now(),
      'blocked_until' => now()->addMinutes($minutes),
      'reason' => $reason
    ], now()->addMinutes($minutes));

    // Log the block
    $this->logSecurityEvent('ip_blocked', [
      'ip' => $ip,
      'duration_minutes' => $minutes,
      'reason' => $reason
    ]);
  }

  /**
   * Unblock IP address
   */
  public function unblockIp(string $ip): void
  {
    Cache::forget("blocked_ip:{$ip}");

    $this->logSecurityEvent('ip_unblocked', ['ip' => $ip]);
  }

  /**
   * Get blocked IP info
   */
  public function getBlockedIpInfo(string $ip): ?array
  {
    return Cache::get("blocked_ip:{$ip}");
  }

  /**
   * Check for suspicious patterns
   */
  public function checkSuspiciousActivity(string $ip): void
  {
    $key = "suspicious_activity:{$ip}";
    $attempts = Cache::get($key, 0) + 1;

    Cache::put($key, $attempts, now()->addHours(1));

    // If too many failed attempts from same IP across different accounts
    if ($attempts >= 20) {
      $this->blockIp($ip, 120, 'Too many failed login attempts');
    }
  }

  /**
   * Get rate limit configuration
   */
  public function getLoginMaxAttempts(): int
  {
    return config('auth.rate_limiting.login.max_attempts', 5);
  }

  public function getLoginDecayMinutes(): int
  {
    return config('auth.rate_limiting.login.decay_minutes', 15);
  }

  /**
   * Log security events
   */
  protected function logSecurityEvent(string $event, array $data): void
  {
    Cache::push('security_events', [
      'event' => $event,
      'data' => $data,
      'timestamp' => now(),
    ]);
  }

  /**
   * Get security events
   */
  public function getSecurityEvents(int $limit = 100): array
  {
    return array_slice(Cache::get('security_events', []), -$limit);
  }

  /**
   * Advanced rate limiting with progressive delays
   */
  public function getProgressiveDelay(string $key, int $attempt): int
  {
    // Progressive delay: 1s, 2s, 4s, 8s, 16s, 32s...
    return min(pow(2, $attempt - 1), 300); // Max 5 minutes
  }

  /**
   * Check if action is rate limited with custom limits
   */
  public function isActionLimited(string $key, int $maxAttempts, int $decayMinutes): bool
  {
    return $this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes);
  }

  /**
   * Hit rate limiter for custom action
   */
  public function hitAction(string $key, int $decayMinutes): void
  {
    $this->limiter->hit($key, $decayMinutes * 60);
  }
}
