<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intervallo minimo tra email di autenticazione
    |--------------------------------------------------------------------------
    |
    | Limite applicato alle richieste HTTP che inviano link di reset password
    | o reinviano la mail di verifica (per utente o combinazione email+IP).
    | Il broker dei token di reset password usa lo stesso intervallo in secondi.
    |
    */

    'resend_minutes' => max(1, (int) env('AUTH_EMAIL_RESEND_MINUTES', 3)),

];
