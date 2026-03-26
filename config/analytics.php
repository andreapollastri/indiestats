<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conservazione dati
    |--------------------------------------------------------------------------
    |
    | Se imposti ANALYTICS_RETENTION_DAYS nel .env, viene usato quel numero di
    | giorni (comportamento legacy). Altrimenti si usa retention_months: pageview,
    | eventi di tracking e click in uscita più vecchi del periodo indicato vengono
    | eliminati dal comando pianificato analytics:prune. Default: 12 mesi.
    |
    */

    'retention_days' => env('ANALYTICS_RETENTION_DAYS') !== null
        ? (int) env('ANALYTICS_RETENTION_DAYS')
        : null,

    'retention_months' => (int) env('ANALYTICS_RETENTION_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | Host aggiuntivi per validazione origine (collect API)
    |--------------------------------------------------------------------------
    |
    | Le richieste di raccolta devono provenire da un host nell’elenco
    | allowed_domains del sito. Qui puoi aggiungere host per sviluppo locale
    | senza modificarli nel DB.
    |
    | Se TRACKING_EXTRA_ALLOWED_HOSTS non è impostato in .env e APP_ENV=local,
    | si usano localhost e 127.0.0.1. Imposta la variabile (anche vuota) per
    | disattivare il default locale.
    |
    */

    'tracking_extra_allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) (env('TRACKING_EXTRA_ALLOWED_HOSTS') !== null
            ? env('TRACKING_EXTRA_ALLOWED_HOSTS')
            : (env('APP_ENV') === 'local' ? 'localhost,127.0.0.1' : '')))
    ))),

];
