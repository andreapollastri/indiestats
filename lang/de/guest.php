<?php

declare(strict_types=1);

return [
    'login' => [
        'document_title' => 'Anmelden · :app',
        'heading' => 'Willkommen zurück',
        'subtitle' => 'Melden Sie sich bei Ihrem Konto an',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'forgot_password' => 'Vergessen?',
        'remember' => 'Angemeldet bleiben',
        'submit' => 'Anmelden',
        'email_placeholder' => 'sie@beispiel.de',
    ],
    'forgot_password' => [
        'document_title' => 'Passwort vergessen · :app',
        'heading' => 'Passwort vergessen',
        'subtitle' => 'Geben Sie Ihre E-Mail ein, um einen Link zum Zurücksetzen zu erhalten.',
        'email' => 'E-Mail',
        'email_placeholder' => 'sie@beispiel.de',
        'submit' => 'Link senden',
        'back_to_login' => 'Zurück zur Anmeldung',
    ],
    'reset_password' => [
        'document_title' => 'Neues Passwort · :app',
        'heading' => 'Neues Passwort',
        'subtitle' => 'Wählen Sie ein neues Passwort für Ihr Konto.',
        'email' => 'E-Mail',
        'password' => 'Passwort',
        'password_placeholder' => 'Neues Passwort',
        'confirm' => 'Bestätigen',
        'confirm_placeholder' => 'Bestätigen',
        'submit' => 'Passwort zurücksetzen',
    ],
    'verify_email' => [
        'document_title' => 'E-Mail bestätigen · :app',
        'heading' => 'Bestätigen Sie Ihre E-Mail',
        'subtitle' => 'Prüfen Sie Ihren Posteingang oder fordern Sie einen neuen Link an.',
        'resend' => 'Bestätigungs-E-Mail erneut senden',
        'logout' => 'Abmelden',
    ],
    'confirm_password' => [
        'document_title' => 'Passwort bestätigen · :app',
        'heading' => 'Passwort bestätigen',
        'subtitle' => 'Geben Sie Ihr Passwort ein, um fortzufahren.',
        'password' => 'Passwort',
        'password_placeholder' => 'Ihr Passwort',
        'submit' => 'Bestätigen',
    ],
    'two_factor' => [
        'document_title' => 'Zwei-Faktor-Authentifizierung · :app',
        'heading' => 'Zwei-Faktor-Authentifizierung',
        'otp_description' => 'Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein.',
        'recovery_code' => 'Wiederherstellungscode',
        'verify' => 'Bestätigen',
        'use_recovery' => 'Wiederherstellungscode verwenden',
        'recovery_description' => 'Geben Sie einen Ihrer Wiederherstellungscodes ein.',
        'use_app_code' => 'Code aus der App verwenden',
        'toggle_use_recovery' => 'Wiederherstellungscode verwenden',
        'toggle_use_app' => 'Code aus der App verwenden',
    ],
];
