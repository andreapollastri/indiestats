<div class="row g-3 mb-4">
    <div class="col-lg-6">
        @include('sites.partials.stats-table', [
            'title' => __('Top pagine'),
            'description' => __('Top percorsi nel periodo'),
            'dtType' => 'paths',
            'dimLabel' => __('Percorso'),
            'site' => $site,
            'range' => $range,
            'cardClass' => 'mb-0 h-100',
        ])
    </div>
    <div class="col-lg-6">
        @include('sites.partials.stats-table', [
            'title' => __('Top sorgenti'),
            'description' => __('Referrer / motore'),
            'dtType' => 'source',
            'dimLabel' => __('Sorgente'),
            'site' => $site,
            'range' => $range,
            'cardClass' => 'mb-0 h-100',
        ])
    </div>
</div>
