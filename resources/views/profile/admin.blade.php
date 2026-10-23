@extends('backend.layouts.app')

@section('title', 'Mon profil - Farm to Fork Admin')

@section('extra-css')
  @include('backend.pages.partials.module-styles')
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / Mon profil</div>
    <h3><i class="mdi mdi-account-settings"></i> Mon profil</h3>
    <p>Gérez vos informations d'administrateur, votre photo et votre mot de passe.</p>
  </div>
</div>

@include('profile._forms', ['user' => $user])
@endsection
