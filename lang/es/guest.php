<?php

declare(strict_types=1);

return [
    'login' => [
        'document_title' => 'Iniciar sesión · :app',
        'heading' => 'Bienvenido de nuevo',
        'subtitle' => 'Accede a tu cuenta',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'forgot_password' => '¿La olvidaste?',
        'remember' => 'Recordarme',
        'submit' => 'Iniciar sesión',
        'email_placeholder' => 'tu@ejemplo.com',
    ],
    'forgot_password' => [
        'document_title' => 'Contraseña olvidada · :app',
        'heading' => 'Contraseña olvidada',
        'subtitle' => 'Introduce tu correo para recibir el enlace de restablecimiento.',
        'email' => 'Correo electrónico',
        'email_placeholder' => 'tu@ejemplo.com',
        'submit' => 'Enviar enlace',
        'back_to_login' => 'Volver al inicio de sesión',
    ],
    'reset_password' => [
        'document_title' => 'Nueva contraseña · :app',
        'heading' => 'Nueva contraseña',
        'subtitle' => 'Elige una nueva contraseña para tu cuenta.',
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'password_placeholder' => 'Nueva contraseña',
        'confirm' => 'Confirmar',
        'confirm_placeholder' => 'Confirmar',
        'submit' => 'Restablecer contraseña',
    ],
    'verify_email' => [
        'document_title' => 'Verificar correo · :app',
        'heading' => 'Verifica tu correo',
        'subtitle' => 'Revisa tu bandeja o solicita un nuevo enlace.',
        'resend' => 'Reenviar correo de verificación',
        'logout' => 'Cerrar sesión',
    ],
    'confirm_password' => [
        'document_title' => 'Confirmar contraseña · :app',
        'heading' => 'Confirmar contraseña',
        'subtitle' => 'Introduce tu contraseña para continuar.',
        'password' => 'Contraseña',
        'password_placeholder' => 'Tu contraseña',
        'submit' => 'Confirmar',
    ],
    'two_factor' => [
        'document_title' => 'Autenticación en dos pasos · :app',
        'heading' => 'Autenticación en dos pasos',
        'otp_description' => 'Introduce el código de 6 dígitos de tu aplicación de autenticación.',
        'recovery_code' => 'Código de recuperación',
        'verify' => 'Verificar',
        'use_recovery' => 'Usar un código de recuperación',
        'recovery_description' => 'Introduce uno de tus códigos de recuperación.',
        'use_app_code' => 'Usar el código de la app',
        'toggle_use_recovery' => 'Usar un código de recuperación',
        'toggle_use_app' => 'Usar el código de la app',
    ],
];
