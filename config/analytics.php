<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seed dati fake
    |--------------------------------------------------------------------------
    |
    | Se true, il seeder FakeDataSeeder popola il database con dati di esempio
    | (5 siti, ~3000 pageview per sito, click in uscita, eventi, goal).
    | Imposta SEED_FAKE_DATA=true nel .env per abilitare.
    |
    */

    'seed_fake_data' => (bool) env('SEED_FAKE_DATA', false),

    /*
    |--------------------------------------------------------------------------
    | Conservazione dati (giorni)
    |--------------------------------------------------------------------------
    |
    | Pageview, eventi di tracking e click in uscita con created_at precedente a
    | oggi meno questo numero di giorni vengono eliminati dal comando
    | analytics:prune (pianificato ogni notte). Default: 375 (circa un anno + 10
    | giorni di margine). Imposta ANALYTICS_RETENTION_DAYS nel .env per sovrascrivere.
    |
    */

    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 375),

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
