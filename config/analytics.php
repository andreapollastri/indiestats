<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fake data seeding
    |--------------------------------------------------------------------------
    |
    | When true, FakeDataSeeder fills the database with sample data
    | (5 sites, ~3000 page views per site, outbound clicks, events, goals).
    | Set SEED_FAKE_DATA=true in .env to enable.
    |
    */

    'seed_fake_data' => (bool) env('SEED_FAKE_DATA', false),

    /*
    |--------------------------------------------------------------------------
    | Data retention (days)
    |--------------------------------------------------------------------------
    |
    | Page views, tracking events, and outbound clicks with created_at older than
    | today minus this many days are removed by the analytics:prune command
    | (scheduled nightly). Default: 375 (about one year + 10 days buffer).
    | Set ANALYTICS_RETENTION_DAYS in .env to override.
    |
    */

    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 375),

    /*
    |--------------------------------------------------------------------------
    | Extra hosts for origin validation (collect API)
    |--------------------------------------------------------------------------
    |
    | Collection requests must come from a host in the site's allowed_domains list.
    | Here you can add hosts for local development without changing the database.
    |
    | If TRACKING_EXTRA_ALLOWED_HOSTS is not set in .env and APP_ENV=local,
    | localhost and 127.0.0.1 are used. Set the variable (even empty) to disable
    | the local default.
    |
    */

    'tracking_extra_allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) (env('TRACKING_EXTRA_ALLOWED_HOSTS') !== null
            ? env('TRACKING_EXTRA_ALLOWED_HOSTS')
            : (env('APP_ENV') === 'local' ? 'localhost,127.0.0.1' : '')))
    ))),

];
