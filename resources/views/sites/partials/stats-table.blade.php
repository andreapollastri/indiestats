@php
    $dtUrl = route('sites.stats.datatables', $site['public_key']);
@endphp
<div class="card mb-4 pa-stats-table-card {{ $cardClass ?? '' }}">
    <div class="card-header py-3">
        <h6 class="m-0">{{ __($title) }}</h6>
        @if (!empty($description))
            <small>{{ __($description) }}</small>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table
                class="table table-bordered table-sm mb-0 w-100 pa-site-dt"
                width="100%"
                data-pa-dt-url="{{ $dtUrl }}"
                data-pa-dt-type="{{ $dtType }}"
                data-pa-dt-range="{{ $range }}"
            >
                <thead>
                    <tr>
                        <th>{{ __($dimLabel) }}</th>
                        <th class="text-end">{{ __('Viste') }}</th>
                        <th class="text-end">{{ __('Univoci') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
