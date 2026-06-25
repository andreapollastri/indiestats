@include('sites.partials.stats-table', [
    'title' => __('Visitatore'),
    'description' => __('Identificativo univoco del visitatore (cookie persistente)'),
    'dtType' => 'visitor_id',
    'dimLabel' => __('Visitatore'),
    'site' => $site,
    'range' => $range,
])

@include('sites.partials.stats-table', [
    'title' => __('Tipo visitatore'),
    'description' => __('Visitatori umani vs bot e crawler'),
    'dtType' => 'is_bot',
    'dimLabel' => __('Tipo'),
    'site' => $site,
    'range' => $range,
])
