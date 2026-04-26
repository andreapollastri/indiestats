<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    | Your personal API token from Boogle profile settings.
    */
    'key' => env('BOOGLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Project Key
    |--------------------------------------------------------------------------
    | The unique key for the project in Boogle.
    */
    'project_key' => env('BOOGLE_PROJECT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Boogle Server URL
    |--------------------------------------------------------------------------
    | The URL of your Boogle installation.
    */
    'server' => env('BOOGLE_SERVER', 'https://boogle.app/api/log'),

    /*
    |--------------------------------------------------------------------------
    | Active Environments
    |--------------------------------------------------------------------------
    | Exceptions are only reported in these environments.
    */
    'environments' => ['production'],

    /*
    |--------------------------------------------------------------------------
    | Context lines
    |--------------------------------------------------------------------------
    | Number of lines of code context to include around the error line.
    */
    'lines_count' => 12,

    /*
    |--------------------------------------------------------------------------
    | Sleep time
    |--------------------------------------------------------------------------
    | Seconds to wait before reporting the same exception again (deduplication).
    */
    'sleep' => 60,

    /*
    |--------------------------------------------------------------------------
    | Ignored exceptions
    |--------------------------------------------------------------------------
    | These exception classes will never be reported to Boogle.
    */
    'except' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklisted keys
    |--------------------------------------------------------------------------
    | These keys will be masked in reported data (e.g. in POST data or headers).
    */
    'blacklist' => [
        'password',
        'password_confirmation',
        'authorization',
        'token',
        'secret',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP snapshot (goes in exception.http)
    |--------------------------------------------------------------------------
    | Control what is included in the JSON. Apply mask via "blacklist" above.
    | Merge extra keys: Boogle::handle($e, "php", ["http" => ["extra" => "value"]]).
    */
    'http' => [
        'include_query'   => true,
        'include_payload' => true,
        'include_cookies' => true,
        'cookie_values'   => true,
        'include_session' => false,
        'include_headers' => false,
    ],
];
