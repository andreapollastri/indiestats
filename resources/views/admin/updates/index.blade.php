@extends('layouts.app')

@php
    /** @var \App\Updates\Updater $updater */
    /** @var array<string, mixed> $state */
    $app = config('app.name');
    $channel = $updater->channel();
    $available = $updater->available();
    $waiting = $updater->waiting();
    $installed = $state['current'] ?? $updater->installed();
    $newest = $channel === 'beta' ? ($state['main'] ?? null) : ($state['latest'] ?? null);
    $ago = fn (?string $time): ?string => $time !== null
        ? \App\Support\RelativeTimeAgoFormatter::format(max(0, now()->getTimestamp() - \Illuminate\Support\Carbon::parse($time)->getTimestamp()))
        : null;
    $lastStatus = $state['status'] ?? null;
@endphp

@section('content')
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 mb-1 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('Updates') }}</h1>
            <p class="small mb-0" style="color: #94a3b8;">{{ __('The release :app runs, the builds it follows and its nightly update.', ['app' => $app]) }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('updates.check') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm" @disabled(! $managed) data-test="updates-check">{{ __('Check now') }}</button>
            </form>
            <form method="POST" action="{{ route('updates.update') }}" onsubmit="return confirm(@js(__('Force an update now? :app installs the newest build of its channel even when it is the one already installed, with the same database backup and rollback as every update. It never goes back to an older build.', ['app' => $app])));">
                @csrf
                <input type="hidden" name="force" value="1">
                <button type="submit" class="btn btn-outline-secondary btn-sm" @disabled(! $managed || $busy) data-test="updates-force">{{ __('Force update') }}</button>
            </form>
            @if ($available)
                <form method="POST" action="{{ route('updates.update') }}" onsubmit="return confirm(@js(__('Update :app to :build now? It backs up the database, installs and migrates the new build, and puts everything back if a step fails. :app is in maintenance for a moment.', ['app' => $app, 'build' => $available])));">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm" @disabled(! $managed || $busy) data-test="updates-apply">{{ __('Update to :build', ['build' => $available]) }}</button>
                </form>
            @endif
        </div>
    </div>

    @include('partials.flash')

    @unless ($managed)
        <div class="alert alert-secondary small mb-4" role="status" data-test="updates-not-managed">
            @if (! config('updates.enabled'))
                {{ __('Updates are off on this installation (a local environment, or APP_UPDATES=false): update it with git.') }}
            @else
                {{ __(':app updates itself when it is a git checkout, or a release of a deploy (releases/, current, shared/, as Cipi and Deployer make them). This installation is neither: update it the way it was installed.', ['app' => $app]) }}
            @endif
        </div>
    @elseif (! $updater->schedulerRunning())
        <div class="alert alert-warning small mb-4" role="status" data-test="updates-scheduler-missing">
            {{ __('The scheduler has not run in the last few minutes. Updates asked for here, and the nightly one, start from it: add :cron to the crontab of the user that runs :app.', ['cron' => '* * * * * php artisan schedule:run', 'app' => $app]) }}
        </div>
    @endunless

    @if ($busy)
        <div
            class="alert alert-info d-flex flex-wrap align-items-center gap-3 mb-4"
            role="status"
            id="pa-update-progress"
            data-status-url="{{ route('updates.status') }}"
            data-switching="{{ __(':app is switching to the new build…', ['app' => $app]) }}"
        >
            <span class="spinner-border spinner-border-sm flex-shrink-0" aria-hidden="true"></span>
            <span class="flex-grow-1" data-update-message>{{ $updater->running() ? ($state['message'] ?? __('The update is running…')) : __('The update starts within a minute…') }}</span>
            <span class="small">{{ __('This page reloads itself when it is done.') }}</span>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card pa-stat-card pa-stat-card--emerald h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="pa-stat-card__label">{{ __('Installed') }}</div>
                            <div class="pa-stat-card__value">{{ $updater->version() }}</div>
                        </div>
                        <div class="pa-stat-card__icon" aria-hidden="true">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <p class="small mt-2 mb-0" style="color: #64748b;">
                        {{ __('Build') }}
                        <a href="{{ $updater->releaseUrl($installed) }}" target="_blank" rel="noopener noreferrer" class="font-monospace">{{ $installed }}</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card pa-stat-card pa-stat-card--cyan h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="pa-stat-card__label">{{ $channel === 'beta' ? __('Newest on main') : __('Newest release') }}</div>
                            <div class="pa-stat-card__value text-truncate">
                                @if ($newest)
                                    <a href="{{ $updater->releaseUrl($newest) }}" target="_blank" rel="noopener noreferrer" class="text-reset text-decoration-none">{{ $newest }}</a>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="pa-stat-card__icon" aria-hidden="true">
                            <i class="fas fa-code-branch"></i>
                        </div>
                    </div>
                    <p class="small mt-2 mb-0 d-flex flex-wrap align-items-center gap-2" style="color: #64748b;">
                        @if ($available)
                            <span class="badge bg-warning text-dark">{{ __('Update available') }}</span>
                        @elseif (isset($state['checked_at']) && ! $waiting)
                            <span class="badge bg-success">{{ __('Up to date') }}</span>
                        @endif
                        <span>{{ isset($state['checked_at']) ? __('Last checked: :time', ['time' => $ago($state['checked_at'])]) : __('Not checked yet') }}</span>
                    </p>
                    @if ($waiting)
                        <p class="small mt-2 mb-0" style="color: #64748b;" data-test="updates-waiting">{{ $waiting }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card pa-stat-card pa-stat-card--violet h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="min-w-0">
                            <div class="pa-stat-card__label">{{ __('Automatic updates') }}</div>
                            <div class="pa-stat-card__value">{{ $updater->automatic() ? __('Every night at :time', ['time' => config('updates.time')]) : __('Off') }}</div>
                        </div>
                        <div class="pa-stat-card__icon" aria-hidden="true">
                            <i class="fas fa-moon"></i>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('updates.automatic') }}" class="mt-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="auto" value="{{ $updater->automatic() ? 0 : 1 }}">
                        <button type="submit" class="btn btn-outline-primary btn-sm" @disabled(! $managed) data-test="updates-automatic">{{ $updater->automatic() ? __('Turn off') : __('Turn on') }}</button>
                    </form>
                    <p class="small mt-2 mb-0" style="color: #94a3b8;">{{ __('The nightly run installs the newest build of the channel when there is one.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="m-0" style="color: #10b981;">{{ __('Channel') }}</h6>
            <form method="POST" action="{{ route('updates.channel') }}" class="btn-group btn-group-sm" role="group" aria-label="{{ __('Channel') }}">
                @csrf
                @method('PUT')
                @foreach (['stable' => __('Stable releases'), 'beta' => __('Beta (main branch)')] as $value => $label)
                    <button
                        type="submit"
                        name="channel"
                        value="{{ $value }}"
                        class="btn {{ $channel === $value ? 'btn-primary' : 'btn-outline-primary' }}"
                        @if ($channel === $value) aria-pressed="true" @endif
                        @disabled(! $managed || $busy)
                    >{{ $label }}</button>
                @endforeach
            </form>
        </div>
        <div class="card-body">
            <p class="small mb-2" style="color: #64748b;">
                @if ($channel === 'beta')
                    {{ __('Beta tester mode: :app follows the main branch and installs every new commit on it, before it becomes a release.', ['app' => $app]) }}
                @else
                    {{ __('Stable: :app installs the release tags (MAJOR.MINOR.PATCH) and nothing in between.', ['app' => $app]) }}
                @endif
            </p>
            <p class="small mb-0" style="color: #94a3b8;">{{ __('Neither channel goes backwards: back on stable after beta, :app keeps its main build until a release contains it.', ['app' => $app]) }}</p>
        </div>
    </div>

    @if ($lastStatus !== null && $lastStatus !== 'running')
        <div class="card mb-4" data-test="updates-last-run">
            <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="m-0" style="color: #10b981;">{{ __('Last update') }}</h6>
                    <span class="badge bg-{{ match ($lastStatus) { 'ok', 'up-to-date' => 'success', 'failed' => 'danger', default => 'secondary' } }}">
                        {{ match ($lastStatus) { 'ok' => __('Updated'), 'up-to-date' => __('Up to date'), 'failed' => __('Failed'), default => $lastStatus } }}
                    </span>
                    @if ($state['triggered_automatically'] ?? false)
                        <span class="small" style="color: #94a3b8;">{{ __('Nightly') }}</span>
                    @endif
                </div>
                <span class="small" style="color: #94a3b8;">{{ $ago($state['finished_at'] ?? null) }}</span>
            </div>
            <div class="card-body">
                <p class="small mb-0" style="color: #0f172a;">{{ $state['message'] ?? '' }}</p>
                @if (! empty($state['log']))
                    <button
                        type="button"
                        class="btn btn-link btn-sm p-0 mt-2"
                        data-bs-toggle="collapse"
                        data-bs-target="#pa-update-log"
                        aria-expanded="{{ $lastStatus === 'failed' ? 'true' : 'false' }}"
                        aria-controls="pa-update-log"
                        data-show-label="{{ __('Show the log') }}"
                        data-hide-label="{{ __('Hide the log') }}"
                    >{{ $lastStatus === 'failed' ? __('Hide the log') : __('Show the log') }}</button>
                    <div class="collapse {{ $lastStatus === 'failed' ? 'show' : '' }}" id="pa-update-log">
                        <pre class="mb-0 mt-2 p-3 rounded small" style="max-height: 24rem; overflow: auto; background: #0f172a; color: #e2e8f0;">{{ implode("\n", $state['log']) }}</pre>
                    </div>
                @endif
                @if ($lastStatus === 'failed')
                    <p class="small mt-3 mb-0" style="color: #94a3b8;">{{ __('The previous build and its database are back in place. The full log is in :path.', ['path' => 'storage/app/updates/update.log']) }}</p>
                @endif
            </div>
        </div>
    @endif

    <p class="small mb-0" style="color: #94a3b8;">
        {{ __('Each update backs up the database, installs the new build (in place for a git checkout, next to the running release for a deploy), installs its dependencies, migrates and checks that :app boots; if a step fails, the previous build and the database come back.', ['app' => $app]) }}
    </p>
@endsection

@push('scripts')
    <script>
        (function () {
            const log = document.getElementById('pa-update-log');
            const toggle = document.querySelector('[data-bs-target="#pa-update-log"]');
            if (log && toggle) {
                log.addEventListener('shown.bs.collapse', function () {
                    toggle.textContent = toggle.dataset.hideLabel;
                });
                log.addEventListener('hidden.bs.collapse', function () {
                    toggle.textContent = toggle.dataset.showLabel;
                });
            }

            const banner = document.getElementById('pa-update-progress');
            if (!banner) {
                return;
            }
            const message = banner.querySelector('[data-update-message]');

            // While the new build goes live the application is in maintenance (503): keep polling.
            setInterval(async function () {
                try {
                    const response = await fetch(banner.dataset.statusUrl, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        message.textContent = banner.dataset.switching;
                        return;
                    }
                    const status = await response.json();
                    if (!status.busy) {
                        window.location.reload();
                        return;
                    }
                    if (status.status === 'running' && status.message) {
                        message.textContent = status.message;
                    }
                } catch (e) {
                    message.textContent = banner.dataset.switching;
                }
            }, 3000);
        })();
    </script>
@endpush
