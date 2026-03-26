<?php

declare(strict_types=1);

return [
    'login' => [
        'document_title' => 'Accedi · :app',
        'heading' => 'Bentornato',
        'subtitle' => 'Accedi al tuo account',
        'email' => 'Email',
        'password' => 'Password',
        'forgot_password' => 'Dimenticata?',
        'remember' => 'Ricordami',
        'submit' => 'Accedi',
        'email_placeholder' => 'nome@esempio.com',
    ],
    'forgot_password' => [
        'document_title' => 'Password dimenticata · :app',
        'heading' => 'Password dimenticata',
        'subtitle' => 'Inserisci la tua email per ricevere il link di reset.',
        'email' => 'Email',
        'email_placeholder' => 'nome@esempio.com',
        'submit' => 'Invia link di reset',
        'back_to_login' => 'Torna al login',
    ],
    'reset_password' => [
        'document_title' => 'Nuova password · :app',
        'heading' => 'Nuova password',
        'subtitle' => 'Scegli una nuova password per il tuo account.',
        'email' => 'Email',
        'password' => 'Password',
        'password_placeholder' => 'Nuova password',
        'confirm' => 'Conferma',
        'confirm_placeholder' => 'Conferma',
        'submit' => 'Reimposta password',
    ],
    'verify_email' => [
        'document_title' => 'Verifica email · :app',
        'heading' => 'Verifica la tua email',
        'subtitle' => 'Controlla la posta o richiedi un nuovo link.',
        'resend' => 'Reinvia email di verifica',
        'logout' => 'Esci',
    ],
    'confirm_password' => [
        'document_title' => 'Conferma password · :app',
        'heading' => 'Conferma password',
        'subtitle' => 'Inserisci la password per continuare.',
        'password' => 'Password',
        'password_placeholder' => 'La tua password',
        'submit' => 'Conferma',
    ],
    'two_factor' => [
        'document_title' => 'Autenticazione a due fattori · :app',
        'heading' => 'Autenticazione 2FA',
        'otp_description' => "Inserisci il codice a 6 cifre dall'app di autenticazione.",
        'recovery_code' => 'Codice di recupero',
        'verify' => 'Verifica',
        'use_recovery' => 'Usa un codice di recupero',
        'recovery_description' => 'Inserisci uno dei codici di recupero.',
        'use_app_code' => "Usa il codice dall'app",
        'toggle_use_recovery' => 'Usa un codice di recupero',
        'toggle_use_app' => "Usa il codice dall'app",
    ],
];
