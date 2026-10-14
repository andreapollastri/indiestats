<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Updates\UpdateFailed;
use App\Updates\Updater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Updates: the installed release, the channel, the nightly update, and an update now.
 * The scheduler runs what is asked here (app:update pending).
 */
class UpdateController extends Controller
{
    public function __construct(private readonly Updater $updater) {}

    public function index(): View
    {
        return view('admin.updates.index', [
            'title' => __('Updates').' · '.config('app.name'),
            'breadcrumbs' => [
                ['title' => __('Updates'), 'href' => route('updates.index')],
            ],
            'updater' => $this->updater,
            'state' => $this->updater->state(),
            'managed' => $this->updater->managed(),
            'busy' => $this->updater->running() || $this->updater->requested(),
        ]);
    }

    /**
     * What the page follows while an update is asked for or running.
     */
    public function status(): JsonResponse
    {
        $state = $this->updater->state();

        return response()->json([
            'busy' => $this->updater->running() || $this->updater->requested(),
            'status' => $state['status'] ?? null,
            'message' => $state['message'] ?? null,
        ]);
    }

    public function check(): RedirectResponse
    {
        try {
            $this->updater->check();
        } catch (UpdateFailed $e) {
            return back()->with('error', $this->updater->waiting() ?? $e->getMessage());
        }

        $available = $this->updater->available();

        return back()->with('success', $available !== null
            ? __(':build is available.', ['build' => $available])
            : __(':app is on the newest build of its channel.', ['app' => config('app.name')]));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($this->updater->managed(), 404);

        if ($this->updater->running() || $this->updater->requested()) {
            return back()->with('error', __('An update is already under way.'));
        }

        $this->updater->request($request->boolean('force'), $request->user()->email);

        return back()->with('success', __('The update starts within a minute.'));
    }

    public function channel(Request $request): RedirectResponse
    {
        $data = $request->validate(['channel' => ['required', 'in:stable,beta']]);
        $this->updater->setChannel($data['channel']);

        return back()->with('success', $data['channel'] === 'beta'
            ? __(':app now follows the main branch.', ['app' => config('app.name')])
            : __(':app now follows the release tags.', ['app' => config('app.name')]));
    }

    public function automatic(Request $request): RedirectResponse
    {
        $this->updater->setAutomatic($request->boolean('auto'));

        return back()->with('success', $this->updater->automatic()
            ? __('Automatic updates are on.')
            : __('Automatic updates are off.'));
    }
}
