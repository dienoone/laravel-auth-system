<?php

return [
    /*
     * Enable/disable 2FA
     */
    'enabled' => env('2FA_ENABLED', true),

    /*
     * Lifetime in minutes.
     */
    'lifetime' => env('2FA_LIFETIME', 0), // 0 = forever

    /*
     * Renew lifetime at every new request.
     */
    'keep_alive' => true,

    /*
     * Auth container binding
     */
    'auth' => 'auth',

    /*
     * 2FA verified session var
     */
    'session_var' => 'google2fa',

    /*
     * One Time Password request input name
     */
    'otp_input' => 'one_time_password',

    /*
     * One Time Password Window
     */
    'window' => 1,

    /*
     * Forbid user to reuse One Time Passwords.
     */
    'forbid_old_passwords' => true,

    /*
     * User's table column for google2fa secret
     */
    'otp_secret_column' => 'two_factor_secret',

    /*
     * One Time Password View
     */
    'view' => 'auth.google2fa.index',

    /*
     * One Time Password error message
     */
    'error_messages' => [
        'wrong_otp' => "The 'One Time Password' typed was wrong.",
        'cannot_be_empty' => 'One Time Password cannot be empty.',
        'unknown' => 'An unknown error has occurred. Please try again.',
    ],

    /*
     * Throw exceptions or just fire events?
     */
    'throw_exceptions' => env('2FA_THROW_EXCEPTIONS', false),

    /*
     * Which image backend to use for generating QR codes?
     */
    'qrcode_image_backend' => 'svg',
];
