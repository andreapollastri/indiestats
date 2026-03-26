<?php

declare(strict_types=1);

return [
    'login' => [
        'document_title' => 'Connexion · :app',
        'heading' => 'Bon retour',
        'subtitle' => 'Connectez-vous à votre compte',
        'email' => 'E-mail',
        'password' => 'Mot de passe',
        'forgot_password' => 'Oublié ?',
        'remember' => 'Se souvenir de moi',
        'submit' => 'Se connecter',
        'email_placeholder' => 'vous@exemple.com',
    ],
    'forgot_password' => [
        'document_title' => 'Mot de passe oublié · :app',
        'heading' => 'Mot de passe oublié',
        'subtitle' => 'Entrez votre e-mail pour recevoir un lien de réinitialisation.',
        'email' => 'E-mail',
        'email_placeholder' => 'vous@exemple.com',
        'submit' => 'Envoyer le lien',
        'back_to_login' => 'Retour à la connexion',
    ],
    'reset_password' => [
        'document_title' => 'Nouveau mot de passe · :app',
        'heading' => 'Nouveau mot de passe',
        'subtitle' => 'Choisissez un nouveau mot de passe pour votre compte.',
        'email' => 'E-mail',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Nouveau mot de passe',
        'confirm' => 'Confirmer',
        'confirm_placeholder' => 'Confirmer',
        'submit' => 'Réinitialiser le mot de passe',
    ],
    'verify_email' => [
        'document_title' => 'Vérifier l’e-mail · :app',
        'heading' => 'Vérifiez votre e-mail',
        'subtitle' => 'Consultez votre boîte ou demandez un nouveau lien.',
        'resend' => 'Renvoyer l’e-mail de vérification',
        'logout' => 'Se déconnecter',
    ],
    'confirm_password' => [
        'document_title' => 'Confirmer le mot de passe · :app',
        'heading' => 'Confirmer le mot de passe',
        'subtitle' => 'Entrez votre mot de passe pour continuer.',
        'password' => 'Mot de passe',
        'password_placeholder' => 'Votre mot de passe',
        'submit' => 'Confirmer',
    ],
    'two_factor' => [
        'document_title' => 'Authentification à deux facteurs · :app',
        'heading' => 'Authentification à deux facteurs',
        'otp_description' => 'Saisissez le code à 6 chiffres de votre application d’authentification.',
        'recovery_code' => 'Code de secours',
        'verify' => 'Vérifier',
        'use_recovery' => 'Utiliser un code de secours',
        'recovery_description' => 'Saisissez l’un de vos codes de secours.',
        'use_app_code' => 'Utiliser le code de l’application',
        'toggle_use_recovery' => 'Utiliser un code de secours',
        'toggle_use_app' => 'Utiliser le code de l’application',
    ],
];
