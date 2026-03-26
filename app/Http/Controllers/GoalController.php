<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Site;
use App\Models\TrackingEvent;
use App\Support\AnalyticsFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GoalController extends Controller
{
    public function store(Request $request, Site $site): RedirectResponse
    {
        $this->authorize('view', $site);

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'event_name' => [
                'required',
                'string',
                'max:128',
                Rule::unique('goals', 'event_name')->where(fn ($q) => $q->where('site_id', $site->id)),
            ],
        ]);

        $site->goals()->create([
            'label' => $data['label'],
            'event_name' => trim($data['event_name']),
        ]);

        return $this->redirectToSiteShowGoalsTab($request, $site)->with('success', 'Evento salvato.');
    }

    public function destroy(Request $request, Site $site, Goal $goal): RedirectResponse
    {
        $this->authorize('view', $site);

        if ($goal->site_id !== $site->id) {
            abort(404);
        }

        $this->authorize('delete', $goal);

        DB::transaction(function () use ($site, $goal): void {
            TrackingEvent::query()
                ->where('site_id', $site->id)
                ->where('name', $goal->event_name)
                ->delete();
            $goal->delete();
        });

        return $this->redirectToSiteShowGoalsTab($request, $site)->with('success', 'Evento eliminato.');
    }

    private function redirectToSiteShowGoalsTab(Request $request, Site $site): RedirectResponse
    {
        $range = $request->query('range') ?? $request->input('range');
        $allowed = ['today', '7d', '30d', '3m', '6m', '1y'];
        $params = array_merge(
            ['site' => $site, 'tab' => 'events'],
            AnalyticsFilters::fromRequest($request)->toQueryArray()
        );
        if (is_string($range) && $range !== '' && in_array($range, $allowed, true)) {
            $params['range'] = $range;
        }

        return redirect()->route('sites.show', $params);
    }
}
