<?php

namespace App\Console\Commands;

use App\Services\RateLimitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SecurityManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:manage {action} {--ip=} {--user=} {--minutes=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage security settings (block-ip, unblock-ip, clear-lockouts, show-blocked)';

    protected RateLimitService $rateLimitService;

    public function __construct(RateLimitService $rateLimitService)
    {
        parent::__construct();
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'block-ip' => $this->blockIp(),
            'unblock-ip' => $this->unblockIp(),
            'clear-lockouts' => $this->clearLockouts(),
            'show-blocked' => $this->showBlocked(),
            default => $this->error("Invalid action. Use: block-ip, unblock-ip, clear-lockouts, show-blocked")
        };
    }

    protected function blockIp(): int
    {
        $ip = $this->option('ip');
        $minutes = (int) $this->option('minutes');

        if (!$ip) {
            $this->error('IP address is required (--ip=x.x.x.x)');
            return Command::FAILURE;
        }

        $this->rateLimitService->blockIp($ip, $minutes, 'Blocked via CLI');
        $this->info("IP {$ip} blocked for {$minutes} minutes.");

        return Command::SUCCESS;
    }

    protected function unblockIp(): int
    {
        $ip = $this->option('ip');

        if (!$ip) {
            $this->error('IP address is required (--ip=x.x.x.x)');
            return Command::FAILURE;
        }

        $this->rateLimitService->unblockIp($ip);
        $this->info("IP {$ip} unblocked.");

        return Command::SUCCESS;
    }

    protected function clearLockouts(): int
    {
        $userId = $this->option('user');

        if ($userId) {
            DB::table('users')
                ->where('id', $userId)
                ->update([
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ]);
            $this->info("Lockout cleared for user ID {$userId}.");
        } else {
            $count = DB::table('users')
                ->where('locked_until', '>', now())
                ->update([
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ]);
            $this->info("Cleared lockouts for {$count} users.");
        }

        return Command::SUCCESS;
    }

    protected function showBlocked(): int
    {
        // Show blocked IPs
        $this->info('Blocked IPs:');
        $this->table(
            ['IP', 'Blocked Until', 'Reason'],
            collect($this->getBlockedIps())->map(function ($item) {
                return [
                    $item['ip'],
                    $item['blocked_until'],
                    $item['reason']
                ];
            })
        );

        // Show locked accounts
        $this->info("\nLocked User Accounts:");
        $lockedUsers = DB::table('users')
            ->where('locked_until', '>', now())
            ->select('id', 'email', 'failed_login_attempts', 'locked_until')
            ->get();

        $this->table(
            ['ID', 'Email', 'Failed Attempts', 'Locked Until'],
            $lockedUsers->map(function ($user) {
                return [
                    $user->id,
                    $user->email,
                    $user->failed_login_attempts,
                    $user->locked_until
                ];
            })
        );

        return Command::SUCCESS;
    }

    protected function getBlockedIps(): array
    {
        // This is a simplified version - in production, you'd need to scan Redis/cache keys
        return [];
    }
}
