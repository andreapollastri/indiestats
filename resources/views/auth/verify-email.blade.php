@extends('layouts.guest')

@section('content')
    <div class="text-center mb-4">
        <div class="mb-3">
            <span style="background: rgba(16,185,129,0.08); color: #10b981; width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                <i class="fas fa-envelope"></i>
            </span>
        </div>
        <h1 class="fw-bold mb-1" style="color: #0f172a; font-size: 1.25rem;">{{ __('Verifica la tua email') }}</h1>
        <p class="mb-0" style="color: #94a3b8; font-size: 0.8rem;">{{ __('Controlla la posta o richiedi un nuovo link.') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ $status }}</div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary w-100" style="padding: 0.5rem;">{{ __('Reinvia email di verifica') }}</button>
    </form>
@endsection

@section('footer')
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-link btn-sm p-0" style="color: #94a3b8; text-decoration: none; font-size: 0.8rem;">{{ __('Esci') }}</button>
    </form>
@endsection
