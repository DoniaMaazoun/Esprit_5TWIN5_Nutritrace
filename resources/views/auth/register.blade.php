@extends('frontend.layouts.app')

@section('title', 'Inscription - Farm to Fork')

@php
  $type = old('account_type', 'consumer');
  $field = fn ($name) => $errors->has($name) ? 'is-invalid' : '';
@endphp

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/auth.css') }}">
@endsection

@section('content')
<div class="auth-page">
  @include('auth.partials.visual', [
      'title' => 'Rejoignez la traçabilité <em>de la ferme à l\'assiette</em>',
      'lead' => 'Consommateur, découvrez l\'origine de vos aliments. Producteur, valorisez votre travail auprès de clients exigeants.',
  ])

  <main class="auth-panel">
    <div class="auth-box auth-box--wide">
      <h1>Créer un compte</h1>
      <p class="subtitle">Gratuit, en moins d'une minute. Les champs marqués <span class="text-danger">*</span> sont obligatoires.</p>

      @if($errors->any())
        <div class="auth-alert auth-alert--error" role="alert">
          <i class="fa-solid fa-circle-exclamation"></i>
          <div><strong>{{ $errors->count() }} erreur(s) à corriger</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
      @endif

      <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
        @csrf

        <div class="account-choice" role="radiogroup" aria-label="Type de compte">
          <label>
            <input type="radio" name="account_type" value="consumer" @checked($type !== 'pro')>
            <span class="account-choice__card"><i class="fa-solid fa-basket-shopping"></i><span><strong>Consommateur</strong><small>Je veux connaître l'origine de mes produits</small></span></span>
          </label>
          <label>
            <input type="radio" name="account_type" value="pro" @checked($type === 'pro')>
            <span class="account-choice__card"><i class="fa-solid fa-tractor"></i><span><strong>Professionnel</strong><small>Producteur, transformateur ou distributeur</small></span></span>
          </label>
        </div>

        <div class="auth-field">
          <label for="name">Nom complet <span class="req">*</span></label>
          <div class="auth-input">
            <i class="fa-regular fa-user"></i>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control {{ $field('name') }}" placeholder="Ex. : Amira Ben Salah" autocomplete="name" maxlength="255" required>
          </div>
          <div class="auth-error" data-error-for="name" @unless($errors->has('name')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('name') }}</span></div>
        </div>

        <div class="auth-field">
          <label for="email">Adresse e-mail <span class="req">*</span></label>
          <div class="auth-input">
            <i class="fa-regular fa-envelope"></i>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control {{ $field('email') }}" placeholder="votre@email.com" autocomplete="email" maxlength="255" required>
          </div>
          <div class="auth-error" data-error-for="email" @unless($errors->has('email')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('email') }}</span></div>
        </div>

        <div class="auth-grid">
          <div class="auth-field">
            <label for="password">Mot de passe <span class="req">*</span></label>
            <div class="auth-input">
              <i class="fa-solid fa-lock"></i>
              <input type="password" id="password" name="password" class="form-control {{ $field('password') }}" placeholder="••••••••" autocomplete="new-password" required>
              <button type="button" class="auth-eye" data-toggle-password="password" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
            </div>
            <div class="strength" id="strength" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
            <div class="strength-label" id="strength-label">Au moins 8 caractères (lettres, chiffres, symboles conseillés).</div>
            <div class="auth-error" data-error-for="password" @unless($errors->has('password')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('password') }}</span></div>
          </div>
          <div class="auth-field">
            <label for="password_confirmation">Confirmation <span class="req">*</span></label>
            <div class="auth-input">
              <i class="fa-solid fa-lock"></i>
              <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="••••••••" autocomplete="new-password" required>
              <button type="button" class="auth-eye" data-toggle-password="password_confirmation" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
            </div>
            <div class="auth-error" data-error-for="password_confirmation" hidden><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
          </div>
        </div>

        <div class="pro-fields {{ $type === 'pro' ? 'show' : '' }}" id="proFields">
          <div class="pro-fields__title"><i class="fa-solid fa-building"></i> Informations professionnelles</div>
          <div class="pro-types" role="radiogroup" aria-label="Type d'activité">
            @foreach(['producer' => ['fa-seedling', 'Producteur'], 'processor' => ['fa-industry', 'Transformateur'], 'distributor' => ['fa-truck', 'Distributeur']] as $value => [$icon, $label])
              <label><input type="radio" name="pro_type" value="{{ $value }}" @checked(old('pro_type') === $value)><span><i class="fa-solid {{ $icon }}"></i>{{ $label }}</span></label>
            @endforeach
          </div>
          <div class="auth-error mb-3" data-error-for="pro_type" @unless($errors->has('pro_type')) hidden @endunless style="margin-top:-10px"><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('pro_type') }}</span></div>

          <div class="auth-grid">
            <div class="auth-field">
              <label for="company_name">Établissement / ferme <span class="req">*</span></label>
              <div class="auth-input">
                <i class="fa-solid fa-store"></i>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" class="form-control {{ $field('company_name') }}" placeholder="Ex. : Domaine El Amel" maxlength="255">
              </div>
              <div class="auth-error" data-error-for="company_name" @unless($errors->has('company_name')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('company_name') }}</span></div>
            </div>
            <div class="auth-field">
              <label for="tax_id">Matricule fiscal <span class="req">*</span></label>
              <div class="auth-input">
                <i class="fa-solid fa-receipt"></i>
                <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}" class="form-control {{ $field('tax_id') }}" placeholder="Ex. : 1234567A" maxlength="20">
              </div>
              <div class="auth-error" data-error-for="tax_id" @unless($errors->has('tax_id')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('tax_id') }}</span></div>
            </div>
          </div>
          <div class="auth-alert auth-alert--info"><i class="fa-solid fa-user-shield"></i><div>Votre compte sera vérifié par notre équipe avant activation. Vous recevrez un e-mail de confirmation.</div></div>
        </div>

        <label class="auth-check mb-1"><input type="checkbox" name="terms" id="terms" @checked(old('terms'))> J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a></label>
        <div class="auth-error mb-3" data-error-for="terms" @unless($errors->has('terms')) hidden @endunless><i class="fa-solid fa-circle-exclamation"></i><span>{{ $errors->first('terms') }}</span></div>

        <button type="submit" class="auth-submit mt-3"><i class="fa-solid fa-user-plus"></i> Créer mon compte</button>
      </form>

      <p class="auth-switch">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    </div>
  </main>
</div>
@endsection

@section('extra-js')
  <script src="{{ asset('frontend/js/auth.js') }}"></script>
@endsection
