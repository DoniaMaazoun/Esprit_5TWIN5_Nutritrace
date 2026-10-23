@extends('frontend.layouts.app')

@section('title', 'Mon profil | Farm to Fork')

@section('extra-css')
  <style>
    .pf-shell { background: linear-gradient(180deg, #f0f9f4 0%, var(--off-white, #FAFDF7) 320px); min-height: calc(100vh - 80px); padding: 28px 0 80px; }
    .pf-head { margin-bottom: 22px; }
    .pf-head h1 { font-size: 1.75rem; margin: 0; color: #1B4332; display: flex; align-items: center; gap: 10px; }
    .pf-head p { color: #6B7B6E; margin: 4px 0 0; }
  </style>
@endsection

@section('content')
<div class="pf-shell">
  <div class="container">
    <div class="pf-head">
      <h1><i class="fa-solid fa-user-gear"></i> Mon profil</h1>
      <p>Gérez vos informations, votre photo, votre mot de passe et votre compte.</p>
    </div>
    @include('profile._forms', ['user' => $user])
  </div>
</div>
@endsection
