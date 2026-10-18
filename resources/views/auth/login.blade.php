@extends('frontend.layouts.app')

@section('title', 'Connexion - Farm to Fork')

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
  @include('auth.partials.visual', [
      'title' => 'Bon retour sur <em>Farm to Fork</em>',
      'lead' => 'Connectez-vous pour suivre vos produits, gérer vos exploitations ou administrer la plateforme.',
  ])

  <main class="auth-panel">
    <div class="auth-box">
      <h1>Connexion</h1>
      <p class="subtitle">Accédez à votre espace en quelques secondes.</p>

      @if(session('status'))
        <div class="auth-alert auth-alert--info" role="status"><i class="fa-solid fa-circle-info"></i><div>{{ session('status') }}</div></div>
      @endif
      @if(session('error'))
        <div class="auth-alert auth-alert--warning" role="alert"><i class="fa-solid fa-triangle-exclamation"></i><div>{{ session('error') }}</div></div>
      @endif
      @if($errors->any())
        <div class="auth-alert auth-alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <div><strong>Connexion impossible</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
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

        <div class="auth-field">
          <label for="password">Mot de passe</label>
          <div class="auth-input">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="auth-eye" data-toggle-password="password" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
          </div>
          <div class="auth-error" data-error-for="password" @unless($errors->has('password')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('password') }}</span></div>
        </div>

        <div class="auth-row">
          <label class="auth-check"><input type="checkbox" name="remember" id="remember_me"> Se souvenir de moi</label>
        </div>

        <button type="submit" class="auth-submit"><i class="fa-solid fa-right-to-bracket"></i> Se connecter</button>
      </form>

      @if(app()->environment('local'))
        <div class="auth-divider">Comptes de démonstration (local)</div>
        <div class="auth-demo">
          <button type="button" data-demo="admin@test.tn">Administrateur<small>admin@test.tn</small></button>
          <button type="button" data-demo="producteur@test.tn">Producteur<small>producteur@test.tn</small></button>
          <button type="button" data-demo="producteur2@test.tn">Producteur 2<small>producteur2@test.tn</small></button>
          <button type="button" data-demo="consumer@test.tn">Consommateur<small>consumer@test.tn</small></button>
        </div>
      @endif

      <p class="auth-switch">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
    </div>
  </main>
</div>
@endsection

@section('extra-js')
  <script src="{{ asset('frontend/js/auth.js') }}"></script>
  @if(app()->environment('local'))
    <script>
      document.querySelectorAll('[data-demo]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('email').value = b.dataset.demo;
        document.getElementById('password').value = 'password123';
        document.getElementById('loginForm').requestSubmit();
      }));
    </script>
  @endif
@endsection
