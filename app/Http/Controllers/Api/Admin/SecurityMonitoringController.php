<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\RateLimitService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SecurityMonitoringController extends Controller
{
    use ApiResponseTrait;

    protected RateLimitService $rateLimitService;

    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Get security dashboard data
     */
    public function dashboard(): JsonResponse
    {
        $data = [
            'blocked_ips' => $this->getBlockedIps(),
            'recent_failed_logins' => $this->getRecentFailedLogins(),
            'security_events' => $this->rateLimitService->getSecurityEvents(50),
            'statistics' => $this->getSecurityStatistics(),
        ];

        return $this->successResponse('Security dashboard data retrieved.', $data);
    }

    /**
     * Get blocked IPs
     */
    public function blockedIps(): JsonResponse
    {
        $blockedIps = $this->getBlockedIps();

        return $this->successResponse('Blocked IPs retrieved.', [
            'blocked_ips' => $blockedIps,
            'total' => count($blockedIps)
        ]);
    }

    /**
     * Block an IP manually
     */
    public function blockIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => ['required', 'ip'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:10080'], // Max 1 week
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->rateLimitService->blockIp(
            $request->ip,
            $request->duration_minutes,
            $request->reason
        );

        return $this->successResponse('IP blocked successfully.');
    }

    /**
     * Unblock an IP
     */
    public function unblockIp(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        $this->rateLimitService->unblockIp($request->ip);

        return $this->successResponse('IP unblocked successfully.');
    }

    /**
     * Get failed login attempts
     */
    public function failedLogins(Request $request): JsonResponse
    {
        $hours = $request->input('hours', 24);

        $failedLogins = DB::table('users')
            ->where('failed_login_attempts', '>', 0)
            ->orWhere('locked_until', '>', now())
            ->select('id', 'email', 'username', 'failed_login_attempts', 'locked_until', 'last_login_ip')
            ->orderBy('failed_login_attempts', 'desc')
            ->limit(100)
            ->get();

        return $this->successResponse('Failed login attempts retrieved.', [
            'failed_logins' => $failedLogins,
            'total' => $failedLogins->count()
        ]);
    }

    /**
     * Clear user lockout
     */
    public function clearUserLockout(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        DB::table('users')
            ->where('id', $request->user_id)
            ->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

        return $this->successResponse('User lockout cleared successfully.');
    }

    /**
     * Get blocked IPs from cache
     */
    protected function getBlockedIps(): array
    {
        $keys = Cache::get('blocked_ip:*', []);
        $blockedIps = [];

        foreach ($keys as $key) {
            if (str_starts_with($key, 'blocked_ip:')) {
                $ip = str_replace('blocked_ip:', '', $key);
                $info = Cache::get($key);
                if ($info) {
                    $blockedIps[] = array_merge(['ip' => $ip], $info);
                }
            }
        }

        return $blockedIps;
    }

    /**
     * Get recent failed logins
     */
    protected function getRecentFailedLogins(): array
    {
        return DB::table('users')
            ->where('last_login_at', '>', now()->subHours(24))
            ->where('failed_login_attempts', '>', 0)
            ->select('email', 'failed_login_attempts', 'last_login_ip', 'locked_until')
            ->orderBy('failed_login_attempts', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get security statistics
     */
    protected function getSecurityStatistics(): array
    {
        $stats = [
            'total_locked_accounts' => DB::table('users')
                ->where('locked_until', '>', now())
                ->count(),
            'total_failed_attempts_24h' => DB::table('users')
                ->where('updated_at', '>', now()->subHours(24))
                ->sum('failed_login_attempts'),
            'users_with_2fa' => DB::table('users')
                ->where('two_factor_enabled', true)
                ->count(),
            'total_users' => DB::table('users')->count(),
        ];

        $stats['2fa_adoption_rate'] = $stats['total_users'] > 0
            ? round(($stats['users_with_2fa'] / $stats['total_users']) * 100, 2)
            : 0;

        return $stats;
    }
}
