<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conservazione dati (giorni)
    |--------------------------------------------------------------------------
    |
    | Pageview e click in uscita più vecchi di questo numero di giorni vengono
    | eliminati dal comando pianificato analytics:prune. Default: 365 (1 anno).
    |
    */

    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 365),

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
