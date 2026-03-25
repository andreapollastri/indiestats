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

];
