@include('sites.partials.stats-table', [
    'title' => __('Sorgenti'),
    'description' => __('Referrer / motore'),
    'dtType' => 'source',
    'dimLabel' => __('Sorgente'),
    'site' => $site,
    'range' => $range,
])

@include('sites.partials.stats-table-outbound', [
    'title' => __('Link in uscita'),
    'description' => __('URL di destinazione; provenienza = primo referrer della sessione (come per gli eventi)'),
    'dimLabel' => __('URL destinazione'),
    'site' => $site,
    'range' => $range,
])
