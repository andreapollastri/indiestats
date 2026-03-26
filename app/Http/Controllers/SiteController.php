<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\AnalyticsQueryService;
use App\Services\SiteFilterOptionsService;
use App\Support\AnalyticsFilters;
use App\Support\UserAnalyticsRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(Request $request): View
    {
        $sites = Site::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get()
            ->map(fn (Site $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'public_key' => $s->public_key,
                'allowed_domains' => $s->allowed_domains,
                'embed_code' => $this->embedCode($s),
                'created_at' => $s->created_at->toIso8601String(),
            ]);

        return view('sites.index', [
            'title' => __('Siti').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Siti'), 'href' => route('sites.index')],
            ],
            'sites' => $sites,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_domains' => 'required|string|max:2000',
        ], [
            'allowed_domains.required' => __('Indica almeno un dominio consentito.'),
        ]);

        $allowedDomains = trim($data['allowed_domains']);
        if ($allowedDomains === '') {
            throw ValidationException::withMessages([
                'allowed_domains' => __('Indica almeno un dominio consentito.'),
            ]);
        }

        $request->user()->sites()->create([
            'name' => $data['name'],
            'allowed_domains' => $allowedDomains,
        ]);

        return redirect()->route('sites.index')->with('success', __('Sito aggiunto.'));
    }

    public function show(Request $request, Site $site, AnalyticsQueryService $analytics, SiteFilterOptionsService $filterOptions): View
    {
        $this->authorize('view', $site);

        $range = $request->query('range', '7d');
        $bounds = UserAnalyticsRange::fromRequest($request, $range);
        $range = $bounds['range'];
        $from = $bounds['from'];
        $to = $bounds['to'];

        $filters = AnalyticsFilters::fromRequest($request);
        $stats = $analytics->build($site->id, $from, $to, $filters);

        $siteTab = $request->query('tab', 'summary');
        if ($request->query('analytics') === 'detail') {
            $siteTab = 'detail';
        }
        if (! in_array($siteTab, ['summary', 'detail', 'events'], true)) {
            $siteTab = 'summary';
        }
        $errorsBag = $request->session()->get('errors');
        if ($errorsBag instanceof ViewErrorBag && ($errorsBag->has('label') || $errorsBag->has('event_name'))) {
            $siteTab = 'events';
        }

        $filterPresets = $filterOptions->presetsForAll($site->id, $from, $to);

        $byDayFilled = $analytics->fillDaySeries($stats['by_day'], $from, $to);
        $siteChartPayload = [
            'labels' => collect($byDayFilled)->map(function (array $row) {
                return Carbon::parse($row['date'])->translatedFormat('j M');
            })->all(),
            'data' => array_column($byDayFilled, 'pageviews'),
        ];

        return view('sites.show', [
            'title' => $site->name.' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Siti'), 'href' => route('sites.index')],
                ['title' => $site->name, 'href' => route('sites.show', $site)],
            ],
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'public_key' => $site->public_key,
                'allowed_domains' => $site->allowed_domains,
            ],
            'stats' => $stats,
            'range' => $range,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'site_chart_payload' => $siteChartPayload,
            'analytics_filters' => $filters,
            'filter_presets' => $filterPresets,
            'site_tab' => $siteTab,
        ]);
    }

    public function destroy(Request $request, Site $site): RedirectResponse
    {
        $this->authorize('delete', $site);

        $site->delete();

        return redirect()->route('sites.index')->with('success', 'Sito eliminato.');
    }

    private function embedCode(Site $site): string
    {
        $base = rtrim(config('app.url'), '/');
        $k = $site->public_key;

        return '<script async src="'.$base.'/i/'.$k.'.js"></script>'."\n"
            .'<noscript><img src="'.$base.'/collect/pixel.gif?k='.$k.'&p=/" width="1" height="1" /></noscript>';
    }
}
