<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\AnalyticsQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('Sites/Index', [
            'sites' => $sites,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_domains' => 'nullable|string|max:2000',
        ]);

        $request->user()->sites()->create([
            'name' => $data['name'],
            'allowed_domains' => $data['allowed_domains'] ?? null,
        ]);

        return redirect()->route('sites.index')->with('success', 'Sito aggiunto.');
    }

    public function show(Request $request, Site $site, AnalyticsQueryService $analytics): Response
    {
        $this->authorize('view', $site);

        $range = $request->query('range', '7d');
        $allowed = ['today', '7d', '30d', '3m', '6m', '1y'];
        if (! in_array($range, $allowed, true)) {
            $range = '7d';
        }

        $from = match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            '3m' => now()->subMonths(3)->startOfDay(),
            '6m' => now()->subMonths(6)->startOfDay(),
            '1y' => now()->subYear()->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };
        $to = now()->endOfDay();

        $stats = $analytics->build($site->id, $from, $to);

        return Inertia::render('Sites/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'public_key' => $site->public_key,
                'allowed_domains' => $site->allowed_domains,
                'embed_code' => $this->embedCode($site),
            ],
            'stats' => $stats,
            'range' => $range,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
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
            .'<!-- Eventi: window.indiestats.track(\'nome_evento\', { opzionale: \'valore\' }) -->'."\n"
            .'<noscript><img src="'.$base.'/collect/pixel.gif?k='.$k.'&p=/" width="1" height="1" alt="" /></noscript>';
    }
}
