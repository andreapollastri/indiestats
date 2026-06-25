@extends('layouts.app')

@php
    $rangeLabels = [
        'today' => __('Oggi'),
        '7d' => __('7 giorni'),
        '30d' => __('30 giorni'),
        '3m' => __('3 mesi'),
        '6m' => __('6 mesi'),
        '1y' => __('1 anno'),
    ];
    $siteTab = $site_tab ?? 'summary';
    if ($errors->has('label') || $errors->has('event_name')) {
        $siteTab = 'events';
    }
    $analyticsTabs = [
        'content' => __('Contenuto'),
        'traffic' => __('Traffico'),
        'utm' => __('UTM'),
        'tech' => __('Tecnologia'),
        'geo' => __('Geografia'),
        'visitor' => __('Visitatore'),
    ];
    $summaryTabActive = $siteTab === 'summary';
    $realtimeTabActive = $siteTab === 'realtime';
    $eventsTabActive = $siteTab === 'events';

    $rangeUrls = [];
    foreach ($rangeLabels as $key => $label) {
        $rangeQuery = $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $key]);
        if ($siteTab !== 'summary') {
            $rangeQuery['tab'] = $siteTab;
        }
        $rangeUrls[$key] = route('sites.show', $rangeQuery);
    }

    $pagesPerVisitor = $stats['unique_visitors'] > 0
        ? number_format($stats['total_pageviews'] / $stats['unique_visitors'], 1, ',', '.')
        : '—';
    $outboundRate = $stats['total_pageviews'] > 0
        ? number_format(($stats['outbound_clicks'] / $stats['total_pageviews']) * 100, 1, ',', '.').'%'
        : '—';
@endphp

@section('content')
    <div class="row g-3 align-items-start align-items-lg-center mb-4">
        <div class="col-12 col-lg order-2 order-lg-1">
            <h1 class="h3 mb-1 fw-bold pa-page-header__title">{{ $site['name'] }}</h1>
            <p class="small mb-0 pa-page-header__period">{{ $period['from'] }} — {{ $period['to'] }}</p>
        </div>
        <div class="col-12 col-lg-auto d-flex flex-wrap gap-2 align-items-center justify-content-end order-1 order-lg-2">
            <x-range-pills :ranges="$rangeLabels" :current="$range" :urls="$rangeUrls" class="d-none d-md-flex" />
            <div class="dropdown d-md-none">
                <button
                    class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    type="button"
                    id="pa-site-range-dropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <i class="fas fa-calendar-days me-1" aria-hidden="true"></i>{{ $rangeLabels[$range] ?? $range }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pa-site-range-dropdown">
                    @foreach ($rangeLabels as $key => $label)
                        <li>
                            <a
                                href="{{ $rangeUrls[$key] }}"
                                class="dropdown-item {{ $range === $key ? 'active' : '' }}"
                            >{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <button
                type="button"
                class="btn btn-sm btn-outline-success"
                id="pa-site-export-btn"
                data-pa-export-url="{{ route('sites.exports.store', $site['public_key']) }}"
                data-pa-csrf="{{ csrf_token() }}"
                data-pa-range="{{ $range }}"
                data-pa-export-pending="{{ __('Esportazione in corso…') }}"
                data-pa-export-ready="{{ __('Export pronto. Scarica il file Excel.') }}"
                data-pa-export-failed="{{ __('Esportazione non riuscita.') }}"
            ><i class="fas fa-download me-1" aria-hidden="true"></i>{{ __('Esporta') }}</button>
        </div>
    </div>

    @include('partials.flash')

    @include('sites.partials.site-filters')

    @php
        $dtUrl = route('sites.stats.datatables', $site['public_key']);
    @endphp

    @php
        $tabSummaryHref = route('sites.show', $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $range]));
        $tabRealtimeHref = route('sites.show', $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $range, 'tab' => 'realtime']));
        $tabEventsHref = route('sites.show', $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $range, 'tab' => 'events']));
        $analyticsTabHrefs = [];
        foreach ($analyticsTabs as $tabKey => $tabLabel) {
            $analyticsTabHrefs[$tabKey] = route('sites.show', $analytics_filters->mergeQuery([
                'site' => $site['public_key'],
                'range' => $range,
                'tab' => $tabKey,
            ]));
        }
    @endphp
    <ul class="nav nav-tabs mb-4" id="siteStatsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $summaryTabActive ? 'active' : '' }}"
                id="site-tab-summary"
                href="{{ $tabSummaryHref }}"
                role="tab"
                aria-controls="tab-site-summary"
                aria-selected="{{ $summaryTabActive ? 'true' : 'false' }}"
            >{{ __('Sommario') }}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $realtimeTabActive ? 'active' : '' }}"
                id="site-tab-realtime"
                href="{{ $tabRealtimeHref }}"
                role="tab"
                aria-controls="tab-site-realtime"
                aria-selected="{{ $realtimeTabActive ? 'true' : 'false' }}"
            >{{ __('In tempo reale') }}</a>
        </li>
        @foreach ($analyticsTabs as $tabKey => $tabLabel)
            <li class="nav-item" role="presentation">
                <a
                    class="nav-link {{ $siteTab === $tabKey ? 'active' : '' }}"
                    id="site-tab-{{ $tabKey }}"
                    href="{{ $analyticsTabHrefs[$tabKey] }}"
                    role="tab"
                    aria-controls="tab-site-{{ $tabKey }}"
                    aria-selected="{{ $siteTab === $tabKey ? 'true' : 'false' }}"
                >{{ $tabLabel }}</a>
            </li>
        @endforeach
        <li class="nav-item" role="presentation">
            <a
                class="nav-link {{ $eventsTabActive ? 'active' : '' }}"
                id="site-tab-events"
                href="{{ $tabEventsHref }}"
                role="tab"
                aria-controls="tab-site-events"
                aria-selected="{{ $eventsTabActive ? 'true' : 'false' }}"
            >{{ __('Eventi') }}</a>
        </li>
    </ul>

    <div class="tab-content pt-3" id="siteStatsTabContent">
        @if ($summaryTabActive)
        <div
            class="tab-pane fade show active"
            id="tab-site-summary"
            role="tabpanel"
            aria-labelledby="site-tab-summary"
            tabindex="0"
        >
            <div class="row g-3 mb-3">
                @foreach ([
                    ['label' => __('Visitatori unici'), 'val' => number_format($stats['unique_visitors']), 'icon' => 'fa-users', 'accent' => 'emerald'],
                    ['label' => __('Visualizzazioni'), 'val' => number_format($stats['total_pageviews']), 'icon' => 'fa-eye', 'accent' => 'cyan'],
                    ['label' => __('Tempo medio in pagina'), 'val' => \App\Support\DurationFormatter::formatSeconds($stats['avg_duration_seconds']), 'icon' => 'fa-clock', 'accent' => 'amber'],
                    ['label' => __('Click in uscita'), 'val' => number_format($stats['outbound_clicks']), 'icon' => 'fa-up-right-from-square', 'accent' => 'violet'],
                ] as $box)
                    @include('sites.partials.stat-card', $box)
                @endforeach
            </div>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <span class="pa-insight-chip">
                    <i class="fas fa-layer-group me-1" aria-hidden="true"></i>
                    {{ __('Pagine / visitatore') }}: <strong class="font-monospace">{{ $pagesPerVisitor }}</strong>
                </span>
                <span class="pa-insight-chip">
                    <i class="fas fa-arrow-up-right-from-square me-1" aria-hidden="true"></i>
                    {{ __('Click out / vista') }}: <strong class="font-monospace">{{ $outboundRate }}</strong>
                </span>
            </div>

            @include('sites.partials.summary-highlights', ['site' => $site, 'range' => $range])

            @if (!empty($site_chart_payload['labels']) && $summaryTabActive)
                <div class="card mb-4 pa-stats-table-card">
                    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h6 class="m-0">{{ __('Andamento') }}</h6>
                            <small>{{ $range === 'today' ? __('Visualizzazioni e visitatori per ora') : __('Visualizzazioni e visitatori per giorno') }}</small>
                        </div>
                        <div class="pa-chart-legend d-flex flex-wrap gap-3">
                            <span class="pa-chart-legend__item"><span class="pa-chart-legend__dot pa-chart-legend__dot--pageviews"></span>{{ __('Visualizzazioni') }}</span>
                            <span class="pa-chart-legend__item"><span class="pa-chart-legend__dot pa-chart-legend__dot--visitors"></span>{{ __('Visitatori') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="pa-site-trend-chart-wrap">
                            <canvas id="chart-site-trend" aria-hidden="true"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @endif

        @if ($realtimeTabActive)
        <div
            class="tab-pane fade show active"
            id="tab-site-realtime"
            role="tabpanel"
            aria-labelledby="site-tab-realtime"
            tabindex="0"
        >
            @include('sites.partials.realtime-stats', ['site' => $site])
        </div>
        @endif

        @foreach ($analyticsTabs as $tabKey => $tabLabel)
            @if ($siteTab === $tabKey)
            <div
                class="tab-pane fade show active"
                id="tab-site-{{ $tabKey }}"
                role="tabpanel"
                aria-labelledby="site-tab-{{ $tabKey }}"
                tabindex="0"
            >
                @include('sites.partials.tab-'.$tabKey.'-section', [
                    'site' => $site,
                    'range' => $range,
                    'dtUrl' => $dtUrl,
                ])
            </div>
            @endif
        @endforeach

        @if ($eventsTabActive)
        <div
            class="tab-pane fade show active"
            id="tab-site-events"
            role="tabpanel"
            aria-labelledby="site-tab-events"
            tabindex="0"
        >
    <div class="card mb-4 pa-stats-table-card">
        <div class="card-header py-3">
            <h6 class="m-0">{{ __('Eventi configurati') }}</h6>
            <small>{{ __('Descrizione in dashboard e tag inviato con indiestats.track (stesso valore della stringa nel codice).') }}</small>
            <small class="d-block mt-1 pa-text-muted-soft">{{ __('Volte e visitatori nella tabella: intero periodo sopra, senza i filtri analitici.') }}</small>
        </div>
        <div class="card-body">
            <p class="small mb-2 pa-text-muted-soft">{{ __('Esempio:') }} <code class="user-select-all">window.indiestats.track('nome_tag', { opzionale: 'valore' })</code></p>
            <form method="POST" action="{{ route('sites.goals.store', $site['public_key']) }}" class="mb-4">
                @csrf
                <input type="hidden" name="range" value="{{ $range }}">
                <input type="hidden" name="tab" value="events">
                @foreach ($analytics_filters->toQueryArray() as $fk => $fv)
                    <input type="hidden" name="{{ $fk }}" value="{{ $fv }}">
                @endforeach
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="g-label" class="form-label">{{ __('Descrizione') }}</label>
                        <input id="g-label" name="label" type="text" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}" required placeholder="{{ __('Iscrizione completata') }}" autocomplete="off">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="g-ev" class="form-label">{{ __('Tag') }}</label>
                        <input id="g-ev" name="event_name" type="text" class="form-control font-monospace @error('event_name') is-invalid @enderror" value="{{ old('event_name') }}" required placeholder="signup_complete" autocomplete="off">
                        @error('event_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Aggiungi') }}</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table
                    class="table table-bordered table-sm mb-0 w-100 pa-site-dt"
                    width="100%"
                    data-pa-dt-url="{{ $dtUrl }}"
                    data-pa-dt-type="goals"
                    data-pa-dt-range="{{ $range }}"
                    data-pa-dt-confirm-delete="{{ __('Eliminare questo evento?') }}"
                    data-pa-dt-remove-label="{{ __('Rimuovi') }}"
                >
                    <thead>
                        <tr>
                            <th>{{ __('Descrizione') }}</th>
                            <th class="font-monospace">{{ __('Tag') }}</th>
                            <th class="text-end">{{ __('Volte') }}</th>
                            <th class="text-end">{{ __('Visitatori') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4 pa-stats-table-card">
        <div class="card-header py-3">
            <h6 class="m-0">{{ __('Eventi') }}</h6>
            <small>{{ __('Tutti i tag inviati con indiestats.track nel periodo') }}</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered table-sm mb-0 w-100 pa-site-dt"
                    width="100%"
                    data-pa-dt-url="{{ $dtUrl }}"
                    data-pa-dt-type="event_names"
                    data-pa-dt-range="{{ $range }}"
                >
                    <thead>
                        <tr>
                            <th>{{ __('Tag') }}</th>
                            <th class="text-end">{{ __('Volte') }}</th>
                            <th class="text-end">{{ __('Visitatori') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4 pa-stats-table-card">
        <div class="card-header py-3">
            <h6 class="m-0">{{ __('Dettaglio eventi') }}</h6>
            <small>{{ __('Singole occorrenze nel periodo; payload salvato e ripulito lato server (paginazione server)') }}</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered table-sm mb-0 w-100 pa-site-dt pa-site-dt-events"
                    width="100%"
                    data-pa-dt-url="{{ $dtUrl }}"
                    data-pa-dt-type="events"
                    data-pa-dt-range="{{ $range }}"
                >
                    <thead>
                        <tr>
                            <th class="pa-col-datetime">{{ __('Data/ora') }}</th>
                            <th>{{ __('Tag') }}</th>
                            <th class="pa-col-visitor-id">{{ __('Visitatore') }}</th>
                            <th>{{ __('Percorso') }}</th>
                            <th>{{ __('Payload') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

        </div>
        @endif
    </div>
@endsection

@push('scripts')
    @if (!empty($site_chart_payload['labels']) && $summaryTabActive)
        <script>
            (function () {
                var primary = 'rgb(16, 185, 129)';
                var primaryFill = 'rgba(16, 185, 129, 0.06)';
                var secondary = 'rgb(6, 182, 212)';
                var cfg = @json($site_chart_payload);

                function run() {
                    if (typeof Chart === 'undefined') {
                        requestAnimationFrame(run);
                        return;
                    }
                    var el = document.getElementById('chart-site-trend');
                    if (!el) return;

                    new Chart(el.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: cfg.labels,
                            datasets: [
                                {
                                    label: @json(__('Visualizzazioni')),
                                    data: cfg.pageviews,
                                    borderColor: primary,
                                    backgroundColor: primaryFill,
                                    borderWidth: 1.5,
                                    pointRadius: 0,
                                    pointHoverRadius: 3,
                                    fill: true,
                                    tension: 0.4,
                                },
                                {
                                    label: @json(__('Visitatori')),
                                    data: cfg.visitors,
                                    borderColor: secondary,
                                    backgroundColor: 'transparent',
                                    borderWidth: 1.5,
                                    pointRadius: 0,
                                    pointHoverRadius: 3,
                                    fill: false,
                                    tension: 0.4,
                                },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    intersect: false,
                                    mode: 'index',
                                    backgroundColor: '#0f172a',
                                    titleFont: { family: "'JetBrains Mono', monospace", size: 10 },
                                    bodyFont: { family: "'JetBrains Mono', monospace", size: 10 },
                                    padding: 8,
                                    cornerRadius: 6,
                                },
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        maxRotation: 0,
                                        maxTicksLimit: 12,
                                        font: { size: 9, family: "'JetBrains Mono', monospace" },
                                        color: '#94a3b8',
                                    },
                                    border: { display: false },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        font: { size: 9, family: "'JetBrains Mono', monospace" },
                                        color: '#94a3b8',
                                    },
                                    grid: {
                                        color: '#f1f5f9',
                                    },
                                    border: { display: false },
                                },
                            },
                        },
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', run);
                } else {
                    run();
                }
            })();
        </script>
    @endif
@endpush
