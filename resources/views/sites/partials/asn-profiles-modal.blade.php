<div
    class="modal fade"
    id="paAsnProfilesModal"
    tabindex="-1"
    aria-labelledby="paAsnProfilesModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3">
                <div class="min-w-0">
                    <h5 class="modal-title text-truncate" id="paAsnProfilesModalLabel">{{ __('Profili visitatore') }}</h5>
                    <p class="small mb-0 pa-text-muted-soft text-truncate" id="pa-asn-profiles-asn-label"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Chiudi') }}"></button>
            </div>
            <div class="modal-body">
                <div id="pa-asn-profiles-loading" class="text-center py-5 small pa-text-muted-soft d-none">
                    {{ __('Caricamento…') }}
                </div>
                <div id="pa-asn-profiles-empty" class="text-center py-5 small pa-text-muted-soft d-none">
                    {{ __('Nessun profilo per questa rete nel periodo selezionato.') }}
                </div>
                <div id="pa-asn-profiles-error" class="alert alert-danger py-2 small d-none" role="alert"></div>

                <div id="pa-asn-profiles-content" class="d-none">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="small pa-text-muted-soft" id="pa-asn-profiles-counter"></div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="pa-asn-profiles-prev">
                                <i class="fas fa-chevron-left me-1" aria-hidden="true"></i>{{ __('Precedente') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="pa-asn-profiles-next">
                                {{ __('Successivo') }}<i class="fas fa-chevron-right ms-1" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pa-visitor-profile mb-4">
                        <div class="pa-visitor-profile__meta" id="pa-asn-profiles-summary"></div>
                        <div class="small font-monospace text-break user-select-all pa-text-muted-soft mt-2" id="pa-asn-profiles-visitor-id"></div>
                    </div>

                    <div id="pa-asn-profiles-truncated" class="alert alert-warning py-2 small d-none" role="status">
                        {{ __('Timeline troncata: mostrati i primi :count eventi.', ['count' => 500]) }}
                    </div>

                    <div id="pa-asn-profiles-timeline"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $asnProfilesConfig = [
        'visitorsUrlTemplate' => str_replace('/0/', '/__ASN__/', route('sites.stats.asn.visitors', ['site' => $site['public_key'], 'asn' => 0])),
        'timelineUrlTemplate' => route('sites.stats.visitors.timeline', ['site' => $site['public_key'], 'visitorId' => '__VISITOR__']),
        'labels' => [
            'profileCounter' => __('Profilo :current di :total'),
            'pageview' => __('Pagina'),
            'event' => __('Evento'),
            'outbound' => __('Click in uscita'),
            'duration' => __('Durata'),
            'referrer' => __('Provenienza'),
            'path' => __('Percorso'),
            'target' => __('Destinazione'),
            'browser' => __('Browser'),
            'os' => __('Sistema operativo'),
            'device' => __('Dispositivo'),
            'country' => __('Paese'),
            'ip' => __('IP'),
            'ipVaries' => __('IP variabile nel periodo'),
            'visitDays' => __('Giorni di visita'),
            'pageviews' => __('Visualizzazioni'),
            'events' => __('Eventi'),
            'outbounds' => __('Click out'),
            'firstSeen' => __('Prima visita'),
            'lastSeen' => __('Ultima attività'),
            'loadFailed' => __('Impossibile caricare i profili.'),
            'timelineFailed' => __('Impossibile caricare la timeline.'),
            'openProfiles' => __('Apri profili visitatore'),
        ],
    ];
@endphp
<script type="application/json" id="pa-asn-profiles-config">
@json($asnProfilesConfig)
</script>
