@extends('layouts.app')

@php
    $rangeLabels = [
        'today' => 'Oggi',
        '7d' => '7 giorni',
        '30d' => '30 giorni',
        '3m' => '3 mesi',
        '6m' => '6 mesi',
        '1y' => '1 anno',
    ];
    $statBorders = ['primary', 'success', 'info', 'warning'];
    $siteTab = request()->query('tab', 'summary');
    if (request()->query('analytics') === 'detail') {
        $siteTab = 'detail';
    }
    if (! in_array($siteTab, ['summary', 'detail', 'goals'], true)) {
        $siteTab = 'summary';
    }
    if ($errors->has('label') || $errors->has('event_name')) {
        $siteTab = 'goals';
    }
    $summaryTabActive = $siteTab === 'summary';
    $detailTabActive = $siteTab === 'detail';
    $goalsTabActive = $siteTab === 'goals';
@endphp

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4 flex-column flex-lg-row mt-3">
        <div class="mb-3 mb-lg-0">
            <h1 class="h3 mb-1 text-gray-800">{{ $site['name'] }}</h1>
            <p class="text-muted small mb-0">{{ __('Periodo') }}: {{ $period['from'] }} — {{ $period['to'] }}</p>
        </div>
        <div class="d-flex flex-wrap">
            @foreach ($rangeLabels as $key => $label)
                @php
                    $rangeQuery = ['site' => $site['id'], 'range' => $key];
                    if ($siteTab === 'detail') {
                        $rangeQuery['tab'] = 'detail';
                    } elseif ($siteTab === 'goals') {
                        $rangeQuery['tab'] = 'goals';
                    }
                @endphp
                <a href="{{ route('sites.show', $rangeQuery) }}" class="btn btn-sm mb-1 me-1 {{ $range === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @include('partials.flash')

    @php
        $dtUrl = route('sites.stats.datatables', $site['id']);
    @endphp

    @php
        $tabSummaryHref = route('sites.show', ['site' => $site['id'], 'range' => $range]);
        $tabDetailHref = route('sites.show', ['site' => $site['id'], 'range' => $range, 'tab' => 'detail']);
        $tabGoalsHref = route('sites.show', ['site' => $site['id'], 'range' => $range, 'tab' => 'goals']);
    @endphp
    <ul class="nav nav-tabs mb-4 border-bottom-0" id="siteStatsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a
                class="nav-link fw-bold {{ $summaryTabActive ? 'active' : '' }}"
                id="site-tab-summary"
                href="{{ $tabSummaryHref }}"
                role="tab"
                aria-controls="tab-site-summary"
                aria-selected="{{ $summaryTabActive ? 'true' : 'false' }}"
            >{{ __('Sommario') }}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a
                class="nav-link fw-bold {{ $detailTabActive ? 'active' : '' }}"
                id="site-tab-detail"
                href="{{ $tabDetailHref }}"
                role="tab"
                aria-controls="tab-site-detail"
                aria-selected="{{ $detailTabActive ? 'true' : 'false' }}"
            >{{ __('Dettaglio') }}</a>
        </li>
        <li class="nav-item" role="presentation">
            <a
                class="nav-link fw-bold {{ $goalsTabActive ? 'active' : '' }}"
                id="site-tab-goals"
                href="{{ $tabGoalsHref }}"
                role="tab"
                aria-controls="tab-site-goals"
                aria-selected="{{ $goalsTabActive ? 'true' : 'false' }}"
            >{{ __('Goals') }}</a>
        </li>
    </ul>

    <div class="tab-content" id="siteStatsTabContent">
        <div
            class="tab-pane fade {{ $summaryTabActive ? 'show active' : '' }}"
            id="tab-site-summary"
            role="tabpanel"
            aria-labelledby="site-tab-summary"
            tabindex="0"
        >
            <div class="row">
                @php $icons = ['fa-users', 'fa-eye', 'fa-clock', 'fa-up-right-from-square']; @endphp
                @foreach ([
                    ['label' => __('Visitatori unici'), 'val' => number_format($stats['unique_visitors']), 'raw' => null],
                    ['label' => __('Visualizzazioni'), 'val' => number_format($stats['total_pageviews']), 'raw' => null],
                    ['label' => __('Tempo medio in pagina'), 'val' => \App\Support\DurationFormatter::formatSeconds($stats['avg_duration_seconds']), 'raw' => null],
                    ['label' => __('Click in uscita'), 'val' => number_format($stats['outbound_clicks']), 'raw' => null],
                ] as $idx => $box)
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-{{ $statBorders[$idx] }} shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row g-0 align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs fw-bold text-{{ $statBorders[$idx] }} text-uppercase mb-1">{{ $box['label'] }}</div>
                                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $box['val'] }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas {{ $icons[$idx] }} fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (!empty($site_chart_payload['labels']) && $summaryTabActive)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">{{ __('Andamento') }}</h6>
                        <small class="text-muted">{{ __('Visualizzazioni per giorno') }}</small>
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
                'title' => 'Pagine',
                'description' => 'Top percorsi',
                'dtType' => 'paths',
                'dimLabel' => 'Percorso',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => 'UTM source',
                'description' => 'Campagne etichettate',
                'dtType' => 'utm',
                'dimLabel' => 'utm_source',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => 'Query di ricerca',
                'description' => 'Termini da motori di ricerca o parametri ?q= sulla pagina',
                'dtType' => 'search',
                'dimLabel' => 'Query',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => 'Sorgenti',
                'description' => 'Referrer / motore',
                'dtType' => 'source',
                'dimLabel' => 'Sorgente',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table-outbound', [
                'title' => 'Link in uscita',
                'description' => 'URL di destinazione; provenienza = primo referrer della sessione (come per gli eventi)',
                'dimLabel' => 'URL destinazione',
                'site' => $site,
                'range' => $range,
            ])

            @include('sites.partials.stats-table', [
                'title' => 'Dispositivo',
                'description' => null,
                'dtType' => 'device',
                'dimLabel' => 'Tipo',
                'site' => $site,
                'range' => $range,
            ])

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 fw-bold text-primary">{{ __('Paese') }}</h6>
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
            class="tab-pane fade {{ $goalsTabActive ? 'show active' : '' }}"
            id="tab-site-goals"
            role="tabpanel"
            aria-labelledby="site-tab-goals"
            tabindex="0"
        >
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">{{ __('Goal') }}</h6>
        </div>
        <div class="card-body">
            <p class="small text-muted">{{ __('Conta quante volte viene inviato un evento con un certo nome (uguale a quello in indiestats.track sul sito).') }}</p>
            <form method="POST" action="{{ route('sites.goals.store', $site['id']) }}" class="mb-4">
                @csrf
                <input type="hidden" name="range" value="{{ $range }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="g-label" class="form-label">{{ __('Nome in dashboard') }}</label>
                        <input id="g-label" name="label" type="text" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}" required placeholder="{{ __('Iscrizione completata') }}" autocomplete="off">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="g-ev" class="form-label">{{ __('Nome evento') }}</label>
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
                    data-pa-dt-confirm-delete="{{ __('Eliminare questo goal?') }}"
                    data-pa-dt-remove-label="{{ __('Rimuovi') }}"
                >
                    <thead>
                        <tr>
                            <th>{{ __('Goal') }}</th>
                            <th class="font-monospace">{{ __('evento') }}</th>
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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">{{ __('Eventi') }}</h6>
            <small class="text-muted">{{ __('Tutti i nomi inviati con indiestats.track nel periodo') }}</small>
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
                            <th>{{ __('Nome') }}</th>
                            <th class="text-end">{{ __('Volte') }}</th>
                            <th class="text-end">{{ __('Visitatori') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">{{ __('Dettaglio eventi') }}</h6>
            <small class="text-muted">{{ __('Eventi nel periodo con payload salvato e ripulito lato server (paginazione server)') }}</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-bordered table-sm mb-0 w-100 pa-site-dt"
                    width="100%"
                    data-pa-dt-url="{{ $dtUrl }}"
                    data-pa-dt-type="events"
                    data-pa-dt-range="{{ $range }}"
                >
                    <thead>
                        <tr>
                            <th>{{ __('Data/ora') }}</th>
                            <th>{{ __('Nome') }}</th>
                            <th>{{ __('Percorso') }}</th>
                            <th>{{ __('Provenienza') }}</th>
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
                var primary = 'rgb(78, 115, 223)';
                var primaryFill = 'rgba(78, 115, 223, 0.08)';
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
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 3,
                                    fill: true,
                                    tension: 0.3,
                                },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { intersect: false, mode: 'index' },
                            },
                            scales: {
                                x: {
                                    grid: { display: false, drawBorder: false },
                                    ticks: {
                                        maxRotation: 0,
                                        maxTicksLimit: 12,
                                        font: { size: 10 },
                                        color: '#858796',
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        font: { size: 10 },
                                        color: '#858796',
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)',
                                        drawBorder: false,
                                    },
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
