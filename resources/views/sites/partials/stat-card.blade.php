@php
    /** @var string $label */
    /** @var string $val */
    /** @var string $icon */
    /** @var string $accent one of: emerald, cyan, amber, violet */
    $accentClass = match ($accent ?? 'emerald') {
        'cyan' => 'pa-stat-card--cyan',
        'amber' => 'pa-stat-card--amber',
        'violet' => 'pa-stat-card--violet',
        default => 'pa-stat-card--emerald',
    };
@endphp
<div class="col-xl-3 col-md-6">
    <div class="card pa-stat-card h-100 {{ $accentClass }}">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div class="min-w-0">
                    <div class="pa-stat-card__label">{{ $label }}</div>
                    <div class="pa-stat-card__value">{{ $val }}</div>
                </div>
                <div class="pa-stat-card__icon" aria-hidden="true">
                    <i class="fas {{ $icon }}"></i>
                </div>
            </div>
        </div>
    </div>
</div>
