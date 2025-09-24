<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Basic profile fields
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar');

            // Email verification
            $table->boolean('email_verified')->default(false)->after('email_verified_at');

            // 2FA fields
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes', 1000)->nullable();

            // OAuth fields
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('provider_token', 1000)->nullable();

            // Status and security
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();

            // Indexes
            $table->index('email_verified');
            $table->index('is_active');
            $table->index(['provider', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'avatar',
                'phone',
                'email_verified',
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'provider',
                'provider_id',
                'provider_token',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'failed_login_attempts',
                'locked_until'
            ]);
        });
    }
};
