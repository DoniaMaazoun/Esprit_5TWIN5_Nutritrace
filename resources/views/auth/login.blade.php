@extends('frontend.layouts.app')

@section('title', 'Connexion - Farm to Fork')

@section('extra-css')
<style>
  /* Authentification Page Styles */
  .auth-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 200px);
    background: linear-gradient(135deg, #f0f9f7 0%, #e8f5f3 100%);
    padding: 40px 20px;
  }

  .auth-card {
    border: none;
    border-radius: 1.5rem;
    box-shadow: 0 10px 40px rgba(30, 58, 43, 0.15);
    max-width: 500px;
    width: 100%;
    overflow: hidden;
  }

  .auth-card-header {
    background: linear-gradient(135deg, #1e3a2b 0%, #2d5a27 100%);
    color: white;
    padding: 2rem;
    text-align: center;
    border-bottom: none;
  }

  .auth-card-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
  }

  .auth-card-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.95rem;
  }

  .auth-card-body {
    padding: 2.5rem;
  }

  /* Form Groups */
  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    font-weight: 600;
    color: #1e3a2b;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
  }

  .form-group label i {
    color: #28a745;
    font-size: 1.1rem;
  }

  .form-control {
    border: 2px solid #e0e0e0;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    font-family: 'Nunito', sans-serif;
  }

  .form-control:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
    background-color: #f8fff9;
  }

  .form-control::placeholder {
    color: #a0a0a0;
  }

  /* Checkbox Remember Me */
  .form-check {
    margin: 1.5rem 0;
  }

  .form-check-input {
    border: 2px solid #e0e0e0;
    border-radius: 0.35rem;
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
  }

  .form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
  }

  .form-check-label {
    margin-left: 0.5rem;
    cursor: pointer;
    color: #555;
    font-size: 0.95rem;
  }

  /* Error Messages */
  .alert {
    border: none;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.25rem;
    animation: slideDown 0.3s ease;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .alert-danger {
    background-color: #ffe8e8;
    color: #721c24;
    border: 1px solid #fcc;
  }

  .alert-danger i {
    color: #dc3545;
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-top: 0.15rem;
  }

  .alert-list {
    margin: 0;
    padding: 0;
  }

  .alert-list li {
    list-style: none;
    margin: 0.25rem 0;
  }

  /* Submit Button */
  .btn-login {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    border: none;
    color: white;
    padding: 0.875rem 2rem;
    font-weight: 600;
    border-radius: 2rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    width: 100%;
    margin-top: 1rem;
  }

  .btn-login:hover {
    background: linear-gradient(135deg, #1e7e34 0%, #145a28 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: white;
  }

  .btn-login:active {
    transform: translateY(0);
  }

  /* Auth Footer Links */
  .auth-footer {
    padding: 1.5rem 2.5rem;
    background-color: #f8f9fa;
    text-align: center;
    border-top: 1px solid #e9ecef;
  }

  .auth-footer p {
    margin: 0;
    color: #555;
    font-size: 0.95rem;
  }

  .auth-footer a {
    color: #28a745;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .auth-footer a:hover {
    color: #1e7e34;
    text-decoration: underline;
  }

  /* Logo/Icon */
  .auth-logo {
    font-size: 2.5rem;
    color: white;
  }

  /* Responsive */
  @media (max-width: 576px) {
    .auth-container {
      min-height: auto;
      padding: 20px;
    }

    .auth-card {
      max-width: 100%;
    }

    .auth-card-header {
      padding: 1.5rem;
    }

    .auth-card-header h1 {
      font-size: 1.5rem;
    }

    .auth-card-body {
      padding: 1.5rem;
    }

    .auth-footer {
      padding: 1rem 1.5rem;
    }
  }
</style>
@endsection

@section('content')
<div class="auth-container">
  <div class="card auth-card">
    <!-- Card Header -->
    <div class="auth-card-header">
      <h1>
        <i class="bi bi-leaf auth-logo"></i>
        Connexion
      </h1>
      <p>Accédez à votre compte Farm to Fork</p>
    </div>

    <!-- Card Body -->
    <div class="auth-card-body">
      <!-- Error Messages -->
      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-circle"></i>
          <div>
            <strong>Erreur de connexion</strong>
            <ul class="alert-list mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      <!-- Login Form -->
      <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <!-- Email Input -->
        <div class="form-group">
          <label for="email">
            <i class="bi bi-envelope"></i>
            Adresse E-mail
          </label>
          <input
            type="email"
            class="form-control @error('email') is-invalid @enderror"
            id="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="votre@email.com"
            required
            autofocus
          />
          @error('email')
            <small class="d-block text-danger mt-2">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </small>
          @enderror
        </div>

        <!-- Password Input -->
        <div class="form-group">
          <label for="password">
            <i class="bi bi-lock"></i>
            Mot de passe
          </label>
          <input
            type="password"
            class="form-control @error('password') is-invalid @enderror"
            id="password"
            name="password"
            placeholder="••••••••"
            required
          />
          @error('password')
            <small class="d-block text-danger mt-2">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </small>
          @enderror
        </div>

        <!-- Remember Me Checkbox -->
        <div class="form-check">
          <input
            type="checkbox"
            class="form-check-input"
            id="remember_me"
            name="remember"
          />
          <label class="form-check-label" for="remember_me">
            Se souvenir de moi
          </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-login">
          <i class="bi bi-box-arrow-in-right"></i>
          Se connecter
        </button>
      </form>
    </div>

    <!-- Card Footer -->
    <div class="auth-footer">
      <p>
        Vous n'avez pas de compte ?
        <a href="{{ route('register') }}">
          <i class="bi bi-arrow-right"></i>
          S'inscrire
        </a>
      </p>
    </div>
  </div>
</div>
@endsection
