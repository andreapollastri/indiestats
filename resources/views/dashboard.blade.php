@extends('layouts.app')

@php
    $analytics_filters = $analytics_filters ?? new \App\Support\AnalyticsFilters;
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
    <div class="row g-3 align-items-start align-items-lg-center mb-4">
        <div class="col-12 col-lg order-2 order-lg-1">
            <h1 class="h3 mb-1 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('Dashboard') }}</h1>
            <p class="small mb-0" style="font-family: 'JetBrains Mono', monospace; color: #94a3b8; font-size: 0.75rem;">{{ $period['from'] }} — {{ $period['to'] }}</p>
        </div>
        <div class="col-12 col-lg-auto d-flex flex-wrap gap-1 align-items-center justify-content-end order-1 order-lg-2">
            <div class="dropdown">
                <button
                    class="btn btn-sm btn-outline-secondary dropdown-toggle"
                    type="button"
                    id="pa-dashboard-range-dropdown"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <i class="fas fa-calendar-days me-1" aria-hidden="true"></i>{{ $rangeLabels[$range] ?? $range }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="pa-dashboard-range-dropdown">
                    @foreach ($rangeLabels as $key => $label)
                        <li>
                            <a
                                href="{{ route('dashboard', array_merge(['range' => $key], $analytics_filters->toQueryArray())) }}"
                                class="dropdown-item {{ $range === $key ? 'active' : '' }}"
                            >{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @include('partials.flash')

    @if (empty($sites))
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <div class="mb-3" style="font-size: 2rem; color: #cbd5e1;"><i class="fas fa-chart-line"></i></div>
                <p class="text-muted mb-3">{{ __('Non hai ancora siti. Aggiungine uno per vedere le statistiche qui.') }}</p>
                <a href="{{ route('sites.index') }}" class="btn btn-primary">{{ __('Vai ai siti') }}</a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($sites as $site)
                <div class="col-xl-4 col-lg-6 mb-4">
                    <div class="card h-100 border-left-primary overflow-hidden">
                        <div class="card-body position-relative pb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2 pe-2">
                                <h2 class="h6 fw-bold mb-0" style="color: #0f172a;">{{ $site['name'] }}</h2>
                                <span class="badge rounded-pill" style="background: rgba(16,185,129,0.08); color: #10b981; font-size: 0.7rem;" title="{{ __('Visualizzazioni') }}">{{ number_format($site['total_pageviews']) }}</span>
                            </div>
                            <p class="text-xs mb-3" style="color: #94a3b8;">
                                {{ __('Visitatori unici') }}: <span class="fw-bold" style="color: #334155;">{{ number_format($site['unique_visitors']) }}</span>
                            </p>
                            <div class="pa-dashboard-chart-wrap mb-2">
                                <canvas id="chart-site-{{ $site['id'] }}" aria-hidden="true"></canvas>
                            </div>
                            <p class="text-xs text-center mb-0 fw-bold" style="color: #10b981;">
                                <i class="fas fa-arrow-right me-1"></i>{{ __('Apri statistiche') }}
                            </p>
                            <a href="{{ route('sites.show', array_merge(['site' => $site['public_key'], 'range' => $range], $analytics_filters->toQueryArray())) }}" class="stretched-link" aria-label="{{ __('Statistiche per :name', ['name' => $site['name']]) }}"></a>
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
                var primary = 'rgb(16, 185, 129)';
                var primaryFill = 'rgba(16, 185, 129, 0.06)';
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
                                        maxTicksLimit: 8,
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
