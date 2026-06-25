@include('sites.partials.stats-table', [
    'title' => __('Browser'),
    'description' => __('Rilevato dal tracciamento (User-Agent)'),
    'dtType' => 'browser',
    'dimLabel' => __('Browser'),
    'site' => $site,
    'range' => $range,
])

@include('sites.partials.stats-table', [
    'title' => __('Sistema operativo'),
    'description' => __('Rilevato dal tracciamento (User-Agent)'),
    'dtType' => 'os',
    'dimLabel' => __('OS'),
    'site' => $site,
    'range' => $range,
])

@include('sites.partials.stats-table', [
    'title' => __('Dispositivo'),
    'description' => null,
    'dtType' => 'device',
    'dimLabel' => __('Tipo'),
    'site' => $site,
    'range' => $range,
])

@include('sites.partials.stats-table', [
    'title' => __('Rete (ASN)'),
    'description' => __('Autonomous System da DB-IP ASN Lite'),
    'dtType' => 'asn',
    'dimLabel' => __('Rete'),
    'site' => $site,
    'range' => $range,
])
