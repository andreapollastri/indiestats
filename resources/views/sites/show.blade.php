@extends('layouts.app')

@php
    $countryLabel = function (?string $code): string {
        if (! $code) {
            return 'Sconosciuto';
        }
        try {
            return \Locale::getDisplayRegion('-'.strtoupper($code), 'it') ?: $code;
        } catch (\Throwable) {
            return $code;
        }
    };
    $rangeLabels = [
        'today' => 'Oggi',
        '7d' => '7 giorni',
        '30d' => '30 giorni',
        '3m' => '3 mesi',
        '6m' => '6 mesi',
        '1y' => '1 anno',
    ];
    $statBorders = ['primary', 'success', 'info', 'warning'];
@endphp

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4 flex-column flex-lg-row mt-3">
        <div class="mb-3 mb-lg-0">
            <h1 class="h3 mb-1 text-gray-800">{{ $site['name'] }}</h1>
            <p class="text-muted small mb-0">{{ __('Periodo') }}: {{ $period['from'] }} — {{ $period['to'] }}</p>
        </div>
        <div class="d-flex flex-wrap">
            @foreach ($rangeLabels as $key => $label)
                <a href="{{ route('sites.show', $site['id']) }}?range={{ $key }}" class="btn btn-sm mb-1 me-1 {{ $range === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @include('partials.flash')

    <div class="row">
        @php $icons = ['fa-users', 'fa-eye', 'fa-clock', 'fa-up-right-from-square']; @endphp
        @foreach ([
            ['label' => __('Visitatori unici'), 'val' => number_format($stats['unique_visitors']), 'raw' => null],
            ['label' => __('Visualizzazioni'), 'val' => number_format($stats['total_pageviews']), 'raw' => null],
            ['label' => __('Tempo medio in pagina'), 'val' => $stats['avg_duration_seconds'] !== null ? $stats['avg_duration_seconds'].'s' : '—', 'raw' => null],
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

    @if (!empty($site_chart_payload['labels']))
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

    @include('sites.partials.stats-table', [
        'title' => 'Pagine',
        'description' => 'Top percorsi',
        'columns' => [['key' => 'path', 'label' => 'Percorso', 'mono' => true]],
        'rows' => $stats['by_path'],
        'valueKeys' => ['pageviews', 'visitors'],
    ])

    @if (!empty($stats['by_utm_source']))
        @include('sites.partials.stats-table', [
            'title' => 'UTM source',
            'description' => 'Campagne etichettate',
            'columns' => [['key' => 'utm_source', 'label' => 'utm_source', 'mono' => false]],
            'rows' => $stats['by_utm_source'],
            'valueKeys' => ['pageviews', 'visitors'],
        ])
    @endif

    @if (!empty($stats['by_search_query']))
        @include('sites.partials.stats-table', [
            'title' => 'Query di ricerca',
            'description' => 'Termini da motori di ricerca o parametri ?q= sulla pagina',
            'columns' => [['key' => 'query', 'label' => 'Query', 'mono' => false]],
            'rows' => $stats['by_search_query'],
            'valueKeys' => ['pageviews', 'visitors'],
        ])
    @endif

    @include('sites.partials.stats-table', [
        'title' => 'Sorgenti',
        'description' => 'Referrer / motore',
        'columns' => [['key' => 'source', 'label' => 'Sorgente', 'mono' => false]],
        'rows' => $stats['by_source'],
        'valueKeys' => ['pageviews', 'visitors'],
    ])

    @include('sites.partials.stats-table', [
        'title' => 'Browser',
        'description' => null,
        'columns' => [['key' => 'name', 'label' => 'Browser', 'mono' => false]],
        'rows' => $stats['by_browser'],
        'valueKeys' => ['pageviews', 'visitors'],
    ])

    @include('sites.partials.stats-table', [
        'title' => 'Dispositivo',
        'description' => null,
        'columns' => [['key' => 'name', 'label' => 'Tipo', 'mono' => false]],
        'rows' => $stats['by_device'],
        'valueKeys' => ['pageviews', 'visitors'],
    ])

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">{{ __('Paese') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>{{ __('Paese') }}</th>
                            <th class="text-end">{{ __('Viste') }}</th>
                            <th class="text-end">{{ __('Univoci') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stats['by_country'] as $i => $row)
                            <tr>
                                <td>
                                    {{ $countryLabel($row['code'] ?? null) }}
                                    @if (!empty($row['code']))
                                        <span class="text-muted font-monospace small">({{ $row['code'] }})</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace">{{ $row['pageviews'] }}</td>
                                <td class="text-end font-monospace">{{ $row['visitors'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">{{ __('Goal') }}</h6>
        </div>
        <div class="card-body">
            <p class="small text-muted">{{ __('Conta quante volte viene inviato un evento con un certo nome (uguale a quello in indiestats.track sul sito).') }}</p>
            <form method="POST" action="{{ route('sites.goals.store', $site['id']) }}" class="mb-4">
                @csrf
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

            @if (empty($stats['goals']))
                <p class="small text-muted mb-0">{{ __('Nessun goal. Gli eventi inviati da window.indiestats.track compaiono anche nella tabella “Eventi” sotto.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Goal') }}</th>
                                <th class="font-monospace">{{ __('evento') }}</th>
                                <th class="text-end">{{ __('Volte') }}</th>
                                <th class="text-end">{{ __('Visitatori') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['goals'] as $g)
                                <tr>
                                    <td>{{ $g['label'] }}</td>
                                    <td class="font-monospace small text-muted text-truncate" style="max-width: 10rem;" title="{{ $g['event_name'] }}">{{ $g['event_name'] }}</td>
                                    <td class="text-end font-monospace">{{ $g['count'] }}</td>
                                    <td class="text-end font-monospace">{{ $g['unique_visitors'] }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('sites.goals.destroy', [$site['id'], $g['id']]) }}" class="d-inline" data-confirm="{{ __('Eliminare questo goal?') }}" onsubmit="return confirm(this.dataset.confirm);">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0">{{ __('Rimuovi') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if (!empty($stats['by_event_name']))
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">{{ __('Eventi') }}</h6>
                <small class="text-muted">{{ __('Tutti i nomi inviati con indiestats.track nel periodo') }}</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Nome') }}</th>
                                <th class="text-end">{{ __('Volte') }}</th>
                                <th class="text-end">{{ __('Visitatori') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['by_event_name'] as $row)
                                <tr>
                                    <td class="font-monospace small">{{ $row['name'] }}</td>
                                    <td class="text-end font-monospace">{{ $row['count'] }}</td>
                                    <td class="text-end font-monospace">{{ $row['visitors'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if (!empty($stats['recent_tracking_events']))
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">{{ __('Eventi recenti') }}</h6>
                <small class="text-muted">{{ __('Ultimi eventi nel periodo (max 100), con payload salvato e ripulito lato server') }}</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 w-100">
                        <thead>
                            <tr>
                                <th>{{ __('Data/ora') }}</th>
                                <th>{{ __('Nome') }}</th>
                                <th>{{ __('Percorso') }}</th>
                                <th>{{ __('Visitatore') }}</th>
                                <th>{{ __('Payload') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stats['recent_tracking_events'] as $ev)
                                <tr>
                                    <td class="small text-nowrap font-monospace">{{ \Carbon\Carbon::parse($ev['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                    <td class="font-monospace small">{{ $ev['name'] }}</td>
                                    <td class="font-monospace small text-truncate" style="max-width: 12rem;" title="{{ $ev['path'] ?? '' }}">{{ $ev['path'] ?? '—' }}</td>
                                    <td class="font-monospace small text-muted">{{ $ev['visitor_id_short'] }}</td>
                                    <td class="small">
                                        @if (!empty($ev['properties']))
                                            <ul class="list-unstyled font-monospace mb-0">
                                                @foreach ($ev['properties'] as $pk => $pv)
                                                    <li><span class="text-muted">{{ $pk }}</span>: {{ $pv }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h6 class="m-0 fw-bold text-primary">{{ __('Snippet') }}</h6>
                <small class="text-muted">{{ __('Incolla prima della chiusura di') }} <code>&lt;/body&gt;</code></small>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" data-copy="{{ $site['embed_code'] }}" data-copy-done="{{ __('Copiato') }}"><i class="fas fa-copy me-1"></i>{{ __('Copia') }}</button>
        </div>
        <div class="card-body">
            <pre class="bg-light border rounded p-3 small mb-0 font-monospace" style="max-height: 12rem; overflow: auto; white-space: pre-wrap;">{{ $site['embed_code'] }}</pre>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($site_chart_payload['labels']))
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
