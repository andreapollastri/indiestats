@extends('layouts.app')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold" style="color: #0f172a; letter-spacing: -0.02em;">{{ __('users.create_title') }}</h1>
        <p class="small mb-0" style="color: #94a3b8;">{{ __('users.sites_hint') }}</p>
    </div>

    @include('partials.flash')

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">{{ __('Nome') }}</label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autocomplete="name">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">{{ __('Email') }}</label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">{{ __('Conferma') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">{{ __('Account') }}</label>
                        <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', 'base') === $role->value)>{{ $role->value === 'admin' ? __('users.role_admin') : __('users.role_base') }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @php
                    $selectedRole = old('role', 'base');
                    $isAdminRole = $selectedRole === 'admin';
                @endphp

                <div id="pa-user-sites-assign" class="{{ $isAdminRole ? 'd-none' : '' }}">
                    <h6 class="fw-bold mb-2" style="color: #0f172a;">{{ __('users.sites_assigned') }}</h6>
                    <p class="small text-muted mb-3">{{ __('users.sites_hint') }}</p>
                    <div class="row g-2 mb-4">
                        @foreach ($sites as $site)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="site_ids[]"
                                        value="{{ $site->id }}"
                                        id="site-{{ $site->id }}"
                                        @checked(in_array($site->id, old('site_ids', []), true))
                                    >
                                    <label class="form-check-label" for="site-{{ $site->id }}">{{ $site->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('site_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    @error('site_ids.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                </div>
                <p id="pa-user-sites-admin-note" class="small text-muted mb-4 {{ $isAdminRole ? '' : 'd-none' }}">{{ __('users.sites_admin_note') }}</p>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('users.create_title') }}</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">{{ __('Annulla') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@include('partials.user-role-sites-toggle-script')
