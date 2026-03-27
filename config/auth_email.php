<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Minimum interval between authentication emails
    |--------------------------------------------------------------------------
    |
    | Applied to HTTP requests that send password reset links or resend
    | verification email (per user or email+IP combination).
    | The password reset token broker uses the same interval in seconds.
    |
    */

    'resend_minutes' => max(1, (int) env('AUTH_EMAIL_RESEND_MINUTES', 3)),

];
