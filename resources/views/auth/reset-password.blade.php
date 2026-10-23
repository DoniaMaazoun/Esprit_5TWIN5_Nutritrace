@extends('frontend.layouts.app')

@section('title', 'Nouveau mot de passe - Farm to Fork')

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
  @include('auth.partials.visual', [
      'title' => 'Choisissez un <em>nouveau mot de passe</em>',
      'lead' => 'Utilisez au moins 8 caractères. Une fois validé, vous pourrez vous reconnecter immédiatement.',
  ])

  <main class="auth-panel">
    <div class="auth-box">
      <h1>Nouveau mot de passe</h1>
      <p class="subtitle">Ce lien n'est valable qu'une seule fois.</p>

      @if($errors->any())
        <div class="auth-alert auth-alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <div><strong>Réinitialisation impossible</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
        @if($errors->has('email') && ! $errors->has('password'))
          <p class="auth-hint"><a href="{{ route('password.request') }}">Demander un nouveau lien</a></p>
        @endif
      @endif

      <form method="POST" action="{{ route('password.update') }}" id="resetForm" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="auth-field">
          <label for="email">Adresse e-mail</label>
          <div class="auth-input">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" class="form-control @error('email') is-invalid @enderror"
                   placeholder="votre@email.com" autocomplete="email" required>
          </div>
          <div class="auth-error" data-error-for="email" @unless($errors->has('email')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('email') }}</span></div>
        </div>

        <div class="auth-field">
          <label for="password">Nouveau mot de passe</label>
          <div class="auth-input">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   placeholder="••••••••" autocomplete="new-password" minlength="8" required autofocus>
            <button type="button" class="auth-eye" data-toggle-password="password" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
          </div>
          <div class="auth-hint">8 caractères minimum.</div>
          <div class="auth-error" data-error-for="password" @unless($errors->has('password')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('password') }}</span></div>
        </div>

        <div class="auth-field">
          <label for="password_confirmation">Confirmer le mot de passe</label>
          <div class="auth-input">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                   placeholder="••••••••" autocomplete="new-password" required>
            <button type="button" class="auth-eye" data-toggle-password="password_confirmation" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
          </div>
        </div>

        <button type="submit" class="auth-submit"><i class="fa-solid fa-key"></i> Réinitialiser le mot de passe</button>
      </form>

      <p class="auth-switch"><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-left"></i> Retour à la connexion</a></p>
    </div>
  </main>
</div>
@endsection

@section('extra-js')
  <script src="{{ asset('frontend/js/auth.js') }}"></script>
@endsection
