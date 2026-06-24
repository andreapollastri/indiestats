<div class="card mb-4 pa-stats-table-card pa-realtime-panel" id="pa-realtime-panel">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <span class="pa-realtime-panel__pulse" aria-hidden="true"></span>
            <div class="min-w-0">
                <h6 class="m-0">{{ __('In tempo reale') }}</h6>
                <small class="pa-realtime-panel__updated" id="pa-realtime-updated">{{ __('Caricamento…') }}</small>
            </div>
        </div>
        <div class="pa-chart-legend d-flex flex-wrap gap-3">
            <span class="pa-chart-legend__item"><span class="pa-chart-legend__dot pa-chart-legend__dot--pageviews"></span>{{ __('Visualizzazioni') }}</span>
            <span class="pa-chart-legend__item"><span class="pa-chart-legend__dot pa-chart-legend__dot--visitors"></span>{{ __('Visitatori') }}</span>
        </div>
    </div>
    <div class="card-body pt-3">
        <div class="row g-3 align-items-stretch mb-3">
            <div class="col-6 col-xl-3">
                <div class="pa-realtime-metric h-100">
                    <div class="pa-realtime-metric__label">{{ __('Visitatori attivi ora') }}</div>
                    <div class="pa-realtime-metric__value font-monospace" id="pa-realtime-active">—</div>
                    <div class="pa-realtime-metric__hint">
                        {{ __('Ultimi :minutes min', ['minutes' => \App\Services\RealtimeAnalyticsService::ACTIVE_WINDOW_MINUTES]) }}
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="pa-realtime-metric h-100">
                    <div class="pa-realtime-metric__label">{{ __('Visualizzazioni') }}</div>
                    <div class="pa-realtime-metric__value pa-realtime-metric__value--sm font-monospace" id="pa-realtime-pageviews-5m">—</div>
                    <div class="pa-realtime-metric__hint">
                        {{ __('Ultimi :minutes min', ['minutes' => \App\Services\RealtimeAnalyticsService::ACTIVE_WINDOW_MINUTES]) }}
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="pa-realtime-chart-panel h-100">
                    <div class="pa-realtime-chart-wrap">
                        <canvas id="pa-realtime-chart" aria-hidden="true"></canvas>
                    </div>
                    <p class="small pa-text-muted-soft mb-0 mt-2 text-center">
                        {{ __('Ultimi :minutes minuti', ['minutes' => \App\Services\RealtimeAnalyticsService::SERIES_MINUTES]) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="pa-realtime-recent">
            <div class="pa-realtime-recent__title">{{ __('Ultima attività') }}</div>
            <ul class="list-unstyled mb-0 pa-realtime-recent__list" id="pa-realtime-recent">
                <li class="small pa-text-muted-soft">{{ __('Caricamento…') }}</li>
            </ul>
        </div>
    </div>
</div>

@php
    $realtimeConfig = [
        'url' => route('sites.stats.realtime', $site['public_key']),
        'pollMs' => 15000,
        'labels' => [
            'pageviews' => __('Visualizzazioni'),
            'visitors' => __('Visitatori'),
            'justNow' => __('Adesso'),
            'secondsAgo' => __(':count s fa'),
            'minutesAgo' => __(':count min fa'),
            'noActivity' => __('Nessuna attività recente'),
            'updated' => __('Aggiornato :time'),
            'loading' => __('Caricamento…'),
        ],
    ];
@endphp
<script type="application/json" id="pa-realtime-config">
@json($realtimeConfig)
</script>
