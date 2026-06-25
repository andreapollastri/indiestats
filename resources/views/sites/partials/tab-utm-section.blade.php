@include('sites.partials.stats-table', [
    'title' => __('Click ID campagne'),
    'description' => __('gclid, fbclid e msclkid dalla URL di atterraggio'),
    'dtType' => 'click_ids',
    'dimLabel' => __('Parametro'),
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
