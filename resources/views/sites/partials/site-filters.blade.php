@php
    /** @var \App\Support\AnalyticsFilters $analytics_filters */
    $filterResetUrl = route('sites.show', ['site' => $site['public_key'], 'range' => $range] + ($siteTab === 'summary' ? [] : ['tab' => $siteTab]));
    $filterOptionsUrl = route('sites.stats.filter-options', $site['public_key']);
    $paFilterConfig = [
        'optionsUrl' => $filterOptionsUrl,
        'range' => $range,
        'presets' => $filter_presets,
        'current' => $analytics_filters->toQueryArray(),
    ];
    $filtersActive = $analytics_filters->hasAny();
    $filterActiveCount = count($analytics_filters->toQueryArray());
@endphp

<form method="get" action="{{ route('sites.show', $site['public_key']) }}" id="pa-site-filters-form" class="mb-3">
    <input type="hidden" name="range" value="{{ $range }}">
    @if ($siteTab !== 'summary')
        <input type="hidden" name="tab" value="{{ $siteTab }}">
    @endif

    <div class="accordion" id="pa-site-filters-accordion">
        <div class="accordion-item border-0">
            <h2 class="accordion-header" id="pa-site-filters-heading">
                <button
                    class="accordion-button collapsed py-2 px-3 rounded-0 shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#pa-site-filters-collapse"
                    aria-expanded="false"
                    aria-controls="pa-site-filters-collapse"
                >
                    <span class="d-flex flex-column flex-sm-row align-items-sm-center gap-1 gap-sm-3 text-start w-100 pe-2">
                        <span class="fw-bold" style="color: #10b981; font-family: 'JetBrains Mono', monospace; font-size: 0.8rem;">{{ __('Filtri') }}</span>
                        @if ($filtersActive)
                            <span class="badge rounded-pill fw-semibold" style="background: rgba(234, 88, 12, 0.18); color: #c2410c; border: 1px solid rgba(234, 88, 12, 0.35);">{{ $filterActiveCount }} {{ $filterActiveCount === 1 ? __('filtro attivo') : __('filtri attivi') }}</span>
                        @endif
                        <span class="small fw-normal" style="color: #94a3b8;">{{ __('Applica dei filtri alle statistiche') }}</span>
                    </span>
                </button>
            </h2>
            <div
                id="pa-site-filters-collapse"
                class="accordion-collapse collapse"
                aria-labelledby="pa-site-filters-heading"
                data-bs-parent="#pa-site-filters-accordion"
            >
                <div class="accordion-body border-top px-3 py-2" style="border-color: #f1f5f9 !important;">
                    <div class="d-flex flex-wrap justify-content-end gap-2 mb-2">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Applica') }}</button>
                        <a href="{{ $filterResetUrl }}" class="btn btn-sm btn-outline-secondary">{{ __('Azzera filtri') }}</a>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-source">{{ __('Provenienza') }}</label>
                            <select name="filter_source" id="pa-f-source" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="source" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->source)
                                    <option value="{{ $analytics_filters->source }}" selected>{{ $analytics_filters->source }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-path">{{ __('Pagina') }}</label>
                            <select name="filter_path" id="pa-f-path" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="path" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->path)
                                    <option value="{{ $analytics_filters->path }}" selected>{{ $analytics_filters->path }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-utm-source">{{ __('UTM source') }}</label>
                            <select name="filter_utm_source" id="pa-f-utm-source" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="utm_source" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->utmSource)
                                    <option value="{{ $analytics_filters->utmSource }}" selected>{{ $analytics_filters->utmSource }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-utm-medium">{{ __('UTM medium') }}</label>
                            <select name="filter_utm_medium" id="pa-f-utm-medium" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="utm_medium" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->utmMedium)
                                    <option value="{{ $analytics_filters->utmMedium }}" selected>{{ $analytics_filters->utmMedium }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-utm-campaign">{{ __('UTM campaign') }}</label>
                            <select name="filter_utm_campaign" id="pa-f-utm-campaign" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="utm_campaign" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->utmCampaign)
                                    <option value="{{ $analytics_filters->utmCampaign }}" selected>{{ $analytics_filters->utmCampaign }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-utm-term">{{ __('UTM term') }}</label>
                            <select name="filter_utm_term" id="pa-f-utm-term" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="utm_term" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->utmTerm)
                                    <option value="{{ $analytics_filters->utmTerm }}" selected>{{ $analytics_filters->utmTerm }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-utm-content">{{ __('UTM content') }}</label>
                            <select name="filter_utm_content" id="pa-f-utm-content" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="utm_content" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutte') }}</option>
                                @if ($analytics_filters->utmContent)
                                    <option value="{{ $analytics_filters->utmContent }}" selected>{{ $analytics_filters->utmContent }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-event">{{ __('Evento') }}</label>
                            <select name="filter_event" id="pa-f-event" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="event" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutti') }}</option>
                                @if ($analytics_filters->event)
                                    <option value="{{ $analytics_filters->event }}" selected>{{ $analytics_filters->event }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-device">{{ __('Dispositivo') }}</label>
                            <select name="filter_device" id="pa-f-device" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="device" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutti') }}</option>
                                @if ($analytics_filters->device)
                                    <option value="{{ $analytics_filters->device }}" selected>{{ $analytics_filters->device }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-browser">{{ __('Browser') }}</label>
                            <select name="filter_browser" id="pa-f-browser" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="browser" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutti') }}</option>
                                @if ($analytics_filters->browser)
                                    <option value="{{ $analytics_filters->browser }}" selected>{{ $analytics_filters->browser }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-os">{{ __('Sistema operativo') }}</label>
                            <select name="filter_os" id="pa-f-os" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="os" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutti') }}</option>
                                @if ($analytics_filters->os)
                                    <option value="{{ $analytics_filters->os }}" selected>{{ $analytics_filters->os }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1" for="pa-f-country">{{ __('Paese') }}</label>
                            <select name="filter_country" id="pa-f-country" class="form-select form-select-sm pa-ts-filter" data-pa-filter-type="country" placeholder="{{ __('Cerca…') }}">
                                <option value="">{{ __('Tutti') }}</option>
                                @if ($analytics_filters->country)
                                    <option value="{{ $analytics_filters->country }}" selected>{{ $analytics_filters->country }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script type="application/json" id="pa-filter-config">
@json($paFilterConfig)
</script>
