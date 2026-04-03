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
    $statBorders = ['primary', 'success', 'info', 'warning'];
    $siteTab = $site_tab ?? 'summary';
    if ($errors->has('label') || $errors->has('event_name')) {
        $siteTab = 'events';
    }
    $summaryTabActive = $siteTab === 'summary';
    $detailTabActive = $siteTab === 'detail';
    $eventsTabActive = $siteTab === 'events';
@endphp

@section('content')
    <div class="row g-3 align-items-start align-items-lg-center mb-4">
        <div class="col-12 col-lg order-2 order-lg-1">
            <h1 class="h3 mb-1 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ $site['name'] }}</h1>
            <p class="small mb-0" style="font-family: 'JetBrains Mono', monospace; color: #94a3b8; font-size: 0.75rem;">{{ $period['from'] }} — {{ $period['to'] }}</p>
        </div>
        <div class="col-12 col-lg-auto d-flex flex-wrap gap-1 align-items-center justify-content-end order-1 order-lg-2">
            <div class="dropdown">
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
                        @php
                            $rangeQuery = $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $key]);
                            if ($siteTab === 'detail') {
                                $rangeQuery['tab'] = 'detail';
                            } elseif ($siteTab === 'events') {
                                $rangeQuery['tab'] = 'events';
                            }
                        @endphp
                        <li>
                            <a
                                href="{{ route('sites.show', $rangeQuery) }}"
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
        $tabDetailHref = route('sites.show', $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $range, 'tab' => 'detail']));
        $tabEventsHref = route('sites.show', $analytics_filters->mergeQuery(['site' => $site['public_key'], 'range' => $range, 'tab' => 'events']));
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
                class="nav-link {{ $detailTabActive ? 'active' : '' }}"
                id="site-tab-detail"
                href="{{ $tabDetailHref }}"
                role="tab"
                aria-controls="tab-site-detail"
                aria-selected="{{ $detailTabActive ? 'true' : 'false' }}"
            >{{ __('Dettaglio') }}</a>
        </li>
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
        <div
            class="tab-pane fade {{ $summaryTabActive ? 'show active' : '' }}"
            id="tab-site-summary"
            role="tabpanel"
            aria-labelledby="site-tab-summary"
            tabindex="0"
        >
            <div class="row">
                @php $icons = ['fa-users', 'fa-eye', 'fa-clock', 'fa-up-right-from-square']; @endphp
                @php $iconColors = ['#10b981', '#06b6d4', '#f59e0b', '#8b5cf6']; @endphp
                @foreach ([
                    ['label' => __('Visitatori unici'), 'val' => number_format($stats['unique_visitors'])],
                    ['label' => __('Visualizzazioni'), 'val' => number_format($stats['total_pageviews'])],
                    ['label' => __('Tempo medio in pagina'), 'val' => \App\Support\DurationFormatter::formatSeconds($stats['avg_duration_seconds'])],
                    ['label' => __('Click in uscita'), 'val' => number_format($stats['outbound_clicks'])],
                ] as $idx => $box)
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-{{ $statBorders[$idx] }} h-100">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-xs fw-medium text-uppercase mb-1" style="color: #94a3b8; letter-spacing: 0.05em;">{{ $box['label'] }}</div>
                                        <div class="h5 mb-0 fw-bold font-monospace" style="color: #0f172a;">{{ $box['val'] }}</div>
                                    </div>
                                    <div style="color: {{ $iconColors[$idx] }}; opacity: 0.3; font-size: 1.5rem;">
                                        <i class="fas {{ $icons[$idx] }}"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (!empty($site_chart_payload['labels']) && $summaryTabActive)
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0" style="color: #10b981;">{{ __('Andamento') }}</h6>
                        <small style="color: #94a3b8;">{{ __('Visualizzazioni per giorno') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="pa-site-trend-chart-wrap">
                            <canvas id="chart-site-trend" aria-hidden="true"></canvas>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div
            class="tab-pane fade {{ $detailTabActive ? 'show active' : '' }}"
            id="tab-site-detail"
            role="tabpanel"
            aria-labelledby="site-tab-detail"
            tabindex="0"
        >
            @include('sites.partials.stats-table', [
                'title' => __('Pagine'),
                'description' => __('Top percorsi'),
                'dtType' => 'paths',
                'dimLabel' => __('Percorso'),
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => __('UTM source'),
                'description' => __('Parametro utm_source dalla pagina di atterraggio'),
                'dtType' => 'utm_source',
                'dimLabel' => 'utm_source',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => __('UTM medium'),
                'description' => __('Parametro utm_medium (es. cpc, email, social)'),
                'dtType' => 'utm_medium',
                'dimLabel' => 'utm_medium',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => __('UTM campaign'),
                'description' => __('Parametro utm_campaign'),
                'dtType' => 'utm_campaign',
                'dimLabel' => 'utm_campaign',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => __('UTM term'),
                'description' => __('Parametro utm_term (parole chiave a pagamento)'),
                'dtType' => 'utm_term',
                'dimLabel' => 'utm_term',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => __('UTM content'),
                'description' => __('Parametro utm_content (varianti A/B o link)'),
                'dtType' => 'utm_content',
                'dimLabel' => 'utm_content',
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

            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0" style="color: #10b981;">{{ __('Paese') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table
                            class="table table-bordered table-sm mb-0 w-100 pa-site-dt"
                            width="100%"
                            data-pa-dt-url="{{ $dtUrl }}"
                            data-pa-dt-type="country"
                            data-pa-dt-range="{{ $range }}"
                        >
                            <thead>
                                <tr>
                                    <th>{{ __('Paese') }}</th>
                                    <th class="text-end">{{ __('Viste') }}</th>
                                    <th class="text-end">{{ __('Univoci') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="tab-pane fade {{ $eventsTabActive ? 'show active' : '' }}"
            id="tab-site-events"
            role="tabpanel"
            aria-labelledby="site-tab-events"
            tabindex="0"
        >
    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Eventi configurati') }}</h6>
            <small style="color: #94a3b8;">{{ __('Descrizione in dashboard e tag inviato con indiestats.track (stesso valore della stringa nel codice).') }}</small>
            <small class="d-block mt-1" style="color: #94a3b8;">{{ __('Volte e visitatori nella tabella: intero periodo sopra, senza i filtri analitici.') }}</small>
        </div>
        <div class="card-body">
            <p class="small mb-2" style="color: #94a3b8;">{{ __('Esempio:') }} <code class="user-select-all">window.indiestats.track('nome_tag', { opzionale: 'valore' })</code></p>
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

    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Eventi') }}</h6>
            <small style="color: #94a3b8;">{{ __('Tutti i tag inviati con indiestats.track nel periodo') }}</small>
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

    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0" style="color: #10b981;">{{ __('Dettaglio eventi') }}</h6>
            <small style="color: #94a3b8;">{{ __('Singole occorrenze nel periodo; payload salvato e ripulito lato server (paginazione server)') }}</small>
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
    </div>
@endsection

@push('scripts')
    @if (!empty($site_chart_payload['labels']) && $summaryTabActive)
        <script>
            (function () {
                var primary = 'rgb(16, 185, 129)';
                var primaryFill = 'rgba(16, 185, 129, 0.06)';
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
                                    data: cfg.data,
                                    borderColor: primary,
                                    backgroundColor: primaryFill,
                                    borderWidth: 1.5,
                                    pointRadius: 0,
                                    pointHoverRadius: 3,
                                    fill: true,
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
