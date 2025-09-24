<?php

namespace App\Console\Commands;

use App\Models\EmailVerificationToken;
use App\Models\PasswordResetToken;
use Illuminate\Console\Command;

class CleanExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:clean-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired verification and password reset tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Delete expired email verification tokens
        $deletedVerificationTokens = EmailVerificationToken::where('expires_at', '<', now())->delete();

        // Delete expired password reset tokens
        $deletedResetTokens = PasswordResetToken::where('expires_at', '<', now())->delete();

        $this->info("Cleaned {$deletedVerificationTokens} expired email verification tokens.");
        $this->info("Cleaned {$deletedResetTokens} expired password reset tokens.");

        return Command::SUCCESS;
    }
}
