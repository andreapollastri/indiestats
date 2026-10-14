<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Updates
    |--------------------------------------------------------------------------
    |
    | The application keeps itself on the newest build of its channel: stable
    | follows the release tags (MAJOR.MINOR.PATCH), beta the head of the main
    | branch. An administrator switches channel, turns the nightly update on or
    | off and starts an update from the Updates page; the scheduler runs it.
    |
    | It updates an installation that is a git checkout in place, and one
    | deployed in releases (Cipi, Deployer: releases/, current, shared/) by
    | building a new release next to the running one and switching to it.
    |
    */

    // on in production; a local checkout is updated with git
    'enabled' => (bool) env('APP_UPDATES', env('APP_ENV', 'production') === 'production'),

    'repository' => env('APP_UPDATE_REPOSITORY', 'https://github.com/andreapollastri/indiestats.git'),

    // the defaults until an administrator chooses on the Updates page
    'channel' => env('APP_UPDATE_CHANNEL', 'stable'),
    'auto' => (bool) env('APP_UPDATE_AUTO', true),

    // the nightly update, in the application's timezone
    'time' => env('APP_UPDATE_TIME', '04:00'),

    // a database backup before every update, put back when the update fails
    'backup' => (bool) env('APP_UPDATE_BACKUP', true),

    // build the assets (npm) when a release does not carry them in public/build
    'assets' => (bool) env('APP_UPDATE_ASSETS', true),

    'state' => storage_path('app/updates/state.json'),

    // the tools; empty: found on the PATH (PHP: the one running the update)
    'php' => env('APP_UPDATE_PHP'),
    'composer' => env('APP_UPDATE_COMPOSER'),
    'npm' => env('APP_UPDATE_NPM'),
    'git' => env('APP_UPDATE_GIT', 'git'),

    'keep_releases' => 3,
    'keep_backups' => 5,

];
