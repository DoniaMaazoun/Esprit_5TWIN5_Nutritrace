@extends('frontend.layouts.app')

@section('title', 'Mot de passe oublié - Farm to Fork')

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
  @include('auth.partials.visual', [
      'title' => 'Un oubli ? <em>Pas de souci</em>',
      'lead' => 'Indiquez votre adresse e-mail : nous vous enverrons un lien sécurisé pour choisir un nouveau mot de passe.',
  ])

  <main class="auth-panel">
    <div class="auth-box">
      <h1>Mot de passe oublié</h1>
      <p class="subtitle">Recevez un lien de réinitialisation valable {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.</p>

      @if(session('status'))
        <div class="auth-alert auth-alert--info" role="status"><i class="fa-solid fa-circle-info"></i><div>{{ session('status') }}</div></div>
      @endif
      @if($errors->any())
        <div class="auth-alert auth-alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <div><strong>Envoi impossible</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
      @endif

      <form method="POST" action="{{ route('password.email') }}" id="forgotForm" novalidate>
        @csrf

        <div class="auth-field">
          <label for="email">Adresse e-mail</label>
          <div class="auth-input">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror"
                   placeholder="votre@email.com" autocomplete="email" required autofocus>
          </div>
          <div class="auth-error" data-error-for="email" @unless($errors->has('email')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('email') }}</span></div>
        </div>

        <button type="submit" class="auth-submit"><i class="fa-solid fa-paper-plane"></i> Envoyer le lien</button>
      </form>

      <p class="auth-switch"><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Retour à la connexion</a></p>
    </div>
  </main>
</div>
@endsection

@section('extra-js')
  <script src="{{ asset('frontend/js/auth.js') }}"></script>
@endsection
