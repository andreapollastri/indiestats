@extends('layouts.app')

@section('content')
    @include('settings.partials.account-section-heading', ['pageTitle' => __('Account')])

    @include('partials.flash')

    @include('settings.partials.account-profile-section')

    @include('settings.partials.account-security-section')

    @include('settings.partials.account-delete-section')
@endsection
