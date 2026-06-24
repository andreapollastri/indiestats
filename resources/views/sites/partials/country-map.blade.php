<div class="card mb-3 pa-stats-table-card pa-country-map-card">
    <div class="card-body py-3">
        <div id="pa-country-map" class="pa-country-map" aria-hidden="true"></div>
        <div class="pa-country-map__legend mt-3">
            <span class="pa-country-map__legend-label">{{ __('Meno visite') }}</span>
            <span class="pa-country-map__legend-bar" aria-hidden="true"></span>
            <span class="pa-country-map__legend-label">{{ __('Più visite') }}</span>
        </div>
    </div>
</div>

@php
    $countryMapConfig = [
        'url' => route('sites.stats.country-map', $site['public_key']),
        'range' => $range,
        'labels' => [
            'pageviews' => __('Visualizzazioni'),
            'visitors' => __('Visitatori'),
            'loading' => __('Caricamento…'),
            'empty' => __('Nessun dato geografico nel periodo'),
        ],
    ];
@endphp
<script type="application/json" id="pa-country-map-config">
@json($countryMapConfig)
</script>
