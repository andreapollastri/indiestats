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
@endphp

@section('content')
    <div class="mb-4 mt-3">
        <div class="d-flex flex-wrap justify-content-start justify-content-lg-end mb-3">
            @foreach ($rangeLabels as $key => $label)
                <a href="{{ route('dashboard', ['range' => $key]) }}" class="btn btn-sm mb-1 me-1 {{ $range === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
        <h1 class="h3 mb-1 text-gray-800">{{ __('Dashboard') }}</h1>
        <p class="text-muted small mb-0">{{ __('Periodo') }}: {{ $period['from'] }} — {{ $period['to'] }}</p>
    </div>

    @include('partials.flash')

    @if (empty($sites))
        <div class="card shadow mb-4">
            <div class="card-body text-center py-5">
                <p class="text-muted mb-3">{{ __('Non hai ancora siti. Aggiungine uno per vedere le statistiche qui.') }}</p>
                <a href="{{ route('sites.index') }}" class="btn btn-primary">{{ __('Vai ai siti') }}</a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($sites as $site)
                <div class="col-xl-4 col-lg-6 mb-4">
                    <div class="card shadow h-100 border-left-primary overflow-hidden">
                        <div class="card-body position-relative pb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2 pe-2">
                                <h2 class="h5 fw-bold text-gray-800 mb-0">{{ $site['name'] }}</h2>
                                <span class="badge rounded-pill bg-light text-primary border" title="{{ __('Visualizzazioni') }}">{{ number_format($site['total_pageviews']) }}</span>
                            </div>
                            <p class="text-xs text-muted mb-3">
                                {{ __('Visitatori unici') }}: <span class="fw-bold text-gray-700">{{ number_format($site['unique_visitors']) }}</span>
                            </p>
                            <div class="pa-dashboard-chart-wrap mb-2">
                                <canvas id="chart-site-{{ $site['id'] }}" aria-hidden="true"></canvas>
                            </div>
                            <p class="text-xs text-center text-primary mb-0 fw-bold">
                                <i class="fas fa-arrow-right me-1"></i>{{ __('Apri statistiche') }}
                            </p>
                            <a href="{{ route('sites.show', $site['id']) }}?range={{ $range }}" class="stretched-link" aria-label="{{ __('Statistiche per :name', ['name' => $site['name']]) }}"></a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
    @if (! empty($sites))
        <script>
            (function () {
                var primary = 'rgb(78, 115, 223)';
                var primaryFill = 'rgba(78, 115, 223, 0.08)';
                var payload = @json($chartPayload);

                function run() {
                    if (typeof Chart === 'undefined') {
                        requestAnimationFrame(run);
                        return;
                    }
                    payload.forEach(function (cfg) {
                    var el = document.getElementById('chart-site-' + cfg.id);
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
                                        maxTicksLimit: 8,
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
