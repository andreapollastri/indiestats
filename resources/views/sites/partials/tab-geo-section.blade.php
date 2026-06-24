@include('sites.partials.country-map', ['site' => $site, 'range' => $range])

<div class="card mb-0 pa-stats-table-card">
    <div class="card-header py-3">
        <h6 class="m-0">{{ __('Paese') }}</h6>
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
