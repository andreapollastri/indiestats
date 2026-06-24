@include('sites.partials.stats-table', [
    'title' => __('Pagine'),
    'description' => __('Top percorsi'),
    'dtType' => 'paths',
    'dimLabel' => __('Percorso'),
    'site' => $site,
    'range' => $range,
])

@include('sites.partials.stats-table', [
    'title' => __('Query di ricerca'),
    'description' => __('Termini da motori di ricerca o parametri ?q= sulla pagina'),
    'dtType' => 'search',
    'dimLabel' => __('Query'),
    'site' => $site,
    'range' => $range,
])
