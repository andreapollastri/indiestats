<?php

declare(strict_types=1);

return [
    'login' => [
        'document_title' => 'Sign in · :app',
        'heading' => 'Welcome back',
        'subtitle' => 'Sign in to your account',
        'email' => 'Email',
        'password' => 'Password',
        'forgot_password' => 'Forgot password?',
        'remember' => 'Remember me',
        'submit' => 'Sign in',
        'email_placeholder' => 'you@example.com',
    ],
    'forgot_password' => [
        'document_title' => 'Forgot password · :app',
        'heading' => 'Forgot password',
        'subtitle' => 'Enter your email to receive a reset link.',
        'email' => 'Email',
        'email_placeholder' => 'you@example.com',
        'submit' => 'Send reset link',
        'back_to_login' => 'Back to sign in',
    ],
    'reset_password' => [
        'document_title' => 'New password · :app',
        'heading' => 'New password',
        'subtitle' => 'Choose a new password for your account.',
        'email' => 'Email',
        'password' => 'Password',
        'password_placeholder' => 'New password',
        'confirm' => 'Confirm',
        'confirm_placeholder' => 'Confirm',
        'submit' => 'Reset password',
    ],
    'verify_email' => [
        'document_title' => 'Verify email · :app',
        'heading' => 'Verify your email',
        'subtitle' => 'Check your inbox or request a new link.',
        'resend' => 'Resend verification email',
        'logout' => 'Log out',
    ],
    'confirm_password' => [
        'document_title' => 'Confirm password · :app',
        'heading' => 'Confirm password',
        'subtitle' => 'Enter your password to continue.',
        'password' => 'Password',
        'password_placeholder' => 'Your password',
        'submit' => 'Confirm',
    ],
    'two_factor' => [
        'document_title' => 'Two-factor authentication · :app',
        'heading' => 'Two-factor authentication',
        'otp_description' => 'Enter the 6-digit code from your authenticator app.',
        'recovery_code' => 'Recovery code',
        'verify' => 'Verify',
        'use_recovery' => 'Use a recovery code',
        'recovery_description' => 'Enter one of your recovery codes.',
        'use_app_code' => 'Use code from the app',
        'toggle_use_recovery' => 'Use a recovery code',
        'toggle_use_app' => 'Use code from the app',
    ],
];
