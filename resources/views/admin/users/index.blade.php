@extends('layouts.app')

@section('content')
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 mb-1 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('users.page_title') }}</h1>
            <p class="small mb-0" style="color: #94a3b8;">{{ __('users.sites_hint') }}</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">{{ __('users.create_title') }}</a>
    </div>

    @include('partials.flash')

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Account') }}</th>
                        <th>{{ __('users.last_login') }}</th>
                        <th class="text-end">{{ __('Impostazioni') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td class="align-middle">{{ $u->name }}</td>
                            <td class="align-middle">{{ $u->email }}</td>
                            <td class="align-middle">
                                <span class="badge bg-{{ $u->role->value === 'admin' ? 'primary' : 'secondary' }}">
                                    {{ $u->role->value === 'admin' ? __('users.role_admin') : __('users.role_base') }}
                                </span>
                            </td>
                            <td class="align-middle small text-nowrap">
                                @if ($u->last_login_at)
                                    {{ $u->last_login_at->timezone($u->timezone ?? config('app.timezone'))->locale(app()->getLocale())->translatedFormat(__('users.last_login_datetime_pattern')) }}
                                @else
                                    <span class="text-muted">{{ __('users.last_login_never') }}</span>
                                @endif
                            </td>
                            <td class="align-middle text-end">
                                <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-primary btn-sm">{{ __('Impostazioni') }}</a>
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $u) }}" class="d-inline" onsubmit="return confirm(@json(__('users.confirm_delete_user')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Elimina') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted small">{{ __('users.no_users') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
