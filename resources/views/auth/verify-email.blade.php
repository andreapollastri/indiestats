@extends('layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="h4 text-gray-900 mb-2">{{ __('Verifica la tua email') }}</h1>
        <p class="small text-muted mb-4">{{ __('Controlla la posta o richiedi un nuovo link.') }}</p>
    </div>
    @if (!empty($status))
        <div class="alert alert-success small">{{ $status }}</div>
    @endif
    <form method="POST" action="{{ route('verification.send') }}" class="user">
        @csrf
        <button type="submit" class="btn btn-primary w-100">{{ __('Reinvia email di verifica') }}</button>
    </form>
@endsection

@section('footer')
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-white-50 p-0">{{ __('Esci') }}</button>
    </form>
@endsection
