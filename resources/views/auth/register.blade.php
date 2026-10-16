@extends('frontend.layouts.app')

@section('title', 'Inscription - Farm to Fork')

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
    max-width: 600px;
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

  .form-group label .required {
    color: #dc3545;
    font-weight: 700;
  }

  .form-control,
  .form-select {
    border: 2px solid #e0e0e0;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    font-family: 'Nunito', sans-serif;
  }

  .form-control:focus,
  .form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
    background-color: #f8fff9;
  }

  .form-control::placeholder {
    color: #a0a0a0;
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

  /* Account Type Selection - Radio Cards */
  .account-type-group {
    margin: 2rem 0;
  }

  .account-type-label {
    font-weight: 700;
    color: #1e3a2b;
    margin-bottom: 1.5rem;
    display: block;
  }

  .account-types {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .account-type-card {
    position: relative;
    cursor: pointer;
  }

  .account-type-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
  }

  .account-type-card-inner {
    border: 2px solid #e0e0e0;
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    background: white;
    cursor: pointer;
  }

  .account-type-card:hover .account-type-card-inner {
    border-color: #28a745;
    background-color: #f8fff9;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
  }

  .account-type-card input[type="radio"]:checked + .account-type-card-inner {
    border-color: #28a745;
    background: linear-gradient(135deg, #f0fdf4 0%, #f0fff4 100%);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
  }

  .account-type-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    display: block;
  }

  .account-type-name {
    font-weight: 700;
    color: #1e3a2b;
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
  }

  .account-type-desc {
    font-size: 0.85rem;
    color: #666;
  }

  /* Professional Section */
  .pro-section {
    display: none;
    background: linear-gradient(135deg, #f0fdf4 0%, #f9fff7 100%);
    border: 2px solid #28a745;
    border-radius: 1rem;
    padding: 1.5rem;
    margin: 2rem 0;
    animation: fadeIn 0.3s ease;
  }

  .pro-section.show {
    display: block;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  .pro-section-title {
    font-weight: 700;
    color: #1e3a2b;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
  }

  .pro-section-title i {
    color: #28a745;
    font-size: 1.3rem;
  }

  /* Info Box */
  .info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    border-radius: 0.5rem;
    padding: 1rem;
    margin: 1.5rem 0;
    display: flex;
    gap: 0.75rem;
  }

  .info-box i {
    color: #2196f3;
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
  }

  .info-box p {
    margin: 0;
    font-size: 0.95rem;
    color: #1565c0;
    line-height: 1.5;
  }

  /* Terms Checkbox */
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

  .form-check-label a {
    color: #28a745;
    text-decoration: none;
    font-weight: 600;
  }

  .form-check-label a:hover {
    text-decoration: underline;
  }

  /* Submit Button */
  .btn-register {
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

  .btn-register:hover {
    background: linear-gradient(135deg, #1e7e34 0%, #145a28 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: white;
  }

  .btn-register:active {
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

    .account-types {
      grid-template-columns: 1fr;
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
        S'inscrire
      </h1>
      <p>Créez votre compte Farm to Fork</p>
    </div>

    <!-- Card Body -->
    <div class="auth-card-body">
      <!-- Error Messages -->
      @if ($errors->any())
        <div class="alert alert-danger" role="alert">
          <i class="bi bi-exclamation-circle"></i>
          <div>
            <strong>Erreur d'inscription</strong>
            <ul class="alert-list mt-2">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      <!-- Registration Form -->
      <form method="POST" action="{{ route('register') }}" id="registerForm" novalidate>
        @csrf

        <!-- Account Type Selection -->
        <div class="account-type-group">
          <label class="account-type-label">
            <i class="bi bi-question-circle"></i>
            Type de compte
          </label>
          <div class="account-types">
            <!-- Consumer Option -->
            <label class="account-type-card">
              <input
                type="radio"
                name="account_type"
                value="consumer"
                checked
                class="account-type-input"
              />
              <div class="account-type-card-inner">
                <span class="account-type-icon">🥗</span>
                <div class="account-type-name">Consommateur</div>
                <div class="account-type-desc">Grand public</div>
              </div>
            </label>

            <!-- Professional Option -->
            <label class="account-type-card">
              <input
                type="radio"
                name="account_type"
                value="pro"
                class="account-type-input"
              />
              <div class="account-type-card-inner">
                <span class="account-type-icon">🚜</span>
                <div class="account-type-name">Professionnel</div>
                <div class="account-type-desc">Producteur, Transformateur, Distributeur</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Name Input -->
        <div class="form-group">
          <label for="name">
            <i class="bi bi-person"></i>
            Nom complet
          </label>
          <input
            type="text"
            class="form-control @error('name') is-invalid @enderror"
            id="name"
            name="name"
            value="{{ old('name') }}"
            placeholder="Jean Dupont"
            required
            autofocus
          />
          @error('name')
            <small class="d-block text-danger mt-2">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </small>
          @enderror
        </div>

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
          <small class="d-block text-muted mt-1">
            <i class="bi bi-info-circle"></i> Minimum 8 caractères
          </small>
          @error('password')
            <small class="d-block text-danger mt-2">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </small>
          @enderror
        </div>

        <!-- Password Confirmation Input -->
        <div class="form-group">
          <label for="password_confirmation">
            <i class="bi bi-lock-check"></i>
            Confirmer le mot de passe
          </label>
          <input
            type="password"
            class="form-control @error('password_confirmation') is-invalid @enderror"
            id="password_confirmation"
            name="password_confirmation"
            placeholder="••••••••"
            required
          />
          @error('password_confirmation')
            <small class="d-block text-danger mt-2">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </small>
          @enderror
        </div>

        <!-- Professional Section (Hidden by default) -->
        <div class="pro-section" id="proSection">
          <div class="pro-section-title">
            <i class="bi bi-building"></i>
            Informations Professionnelles
          </div>

          <!-- Professional Type -->
          <div class="form-group">
            <label for="pro_type">
              <i class="bi bi-briefcase"></i>
              Type d'activité
              <span class="required">*</span>
            </label>
            <select
              class="form-select @error('pro_type') is-invalid @enderror"
              id="pro_type"
              name="pro_type"
            >
              <option value="">-- Sélectionner votre activité --</option>
              <option value="producer" @selected(old('pro_type') === 'producer')>
                🌾 Producteur / Agriculteur
              </option>
              <option value="processor" @selected(old('pro_type') === 'processor')>
                🏭 Transformateur / Usine
              </option>
              <option value="distributor" @selected(old('pro_type') === 'distributor')>
                📦 Distributeur / Logistique
              </option>
            </select>
            @error('pro_type')
              <small class="d-block text-danger mt-2">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
              </small>
            @enderror
          </div>

          <!-- Company Name -->
          <div class="form-group">
            <label for="company_name">
              <i class="bi bi-building"></i>
              Nom de l'établissement / Ferme
              <span class="required">*</span>
            </label>
            <input
              type="text"
              class="form-control @error('company_name') is-invalid @enderror"
              id="company_name"
              name="company_name"
              value="{{ old('company_name') }}"
              placeholder="Ferme du Soleil"
            />
            @error('company_name')
              <small class="d-block text-danger mt-2">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
              </small>
            @enderror
          </div>

          <!-- Tax ID -->
          <div class="form-group">
            <label for="tax_id">
              <i class="bi bi-receipt"></i>
              Identifiant fiscal / Matricule
              <span class="required">*</span>
            </label>
            <input
              type="text"
              class="form-control @error('tax_id') is-invalid @enderror"
              id="tax_id"
              name="tax_id"
              value="{{ old('tax_id') }}"
              placeholder="Ex: 12345678901"
            />
            <small class="d-block text-muted mt-1">
              <i class="bi bi-info-circle"></i> Votre numéro de matricule fiscal
            </small>
            @error('tax_id')
              <small class="d-block text-danger mt-2">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
              </small>
            @enderror
          </div>

          <!-- Info Message -->
          <div class="info-box">
            <i class="bi bi-info-circle"></i>
            <p>
              <strong>Important :</strong> Votre compte professionnel sera soumis à une vérification par notre équipe administrative. Vous recevrez une confirmation par e-mail une fois approuvé.
            </p>
          </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="form-check">
          <input
            type="checkbox"
            class="form-check-input @error('terms') is-invalid @enderror"
            id="terms"
            name="terms"
          />
          <label class="form-check-label" for="terms">
            J'accepte les
            <a href="#">conditions d'utilisation</a>
            et la
            <a href="#">politique de confidentialité</a>
          </label>
          @error('terms')
            <small class="d-block text-danger mt-2">
              <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </small>
          @enderror
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-register">
          <i class="bi bi-check-circle"></i>
          S'inscrire
        </button>
      </form>
    </div>

    <!-- Card Footer -->
    <div class="auth-footer">
      <p>
        Vous avez déjà un compte ?
        <a href="{{ route('login') }}">
          <i class="bi bi-arrow-right"></i>
          Se connecter
        </a>
      </p>
    </div>
  </div>
</div>
@endsection

@section('extra-js')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const accountTypeInputs = document.querySelectorAll('.account-type-input');
    const proSection = document.getElementById('proSection');
    const proTypeSelect = document.getElementById('pro_type');
    const companyNameInput = document.getElementById('company_name');
    const taxIdInput = document.getElementById('tax_id');

    function toggleProSection() {
      const selectedType = document.querySelector('.account-type-input:checked').value;
      
      if (selectedType === 'pro') {
        proSection.classList.add('show');
        proTypeSelect.required = true;
        companyNameInput.required = true;
        taxIdInput.required = true;
      } else {
        proSection.classList.remove('show');
        proTypeSelect.required = false;
        companyNameInput.required = false;
        taxIdInput.required = false;
      }
    }

    accountTypeInputs.forEach(input => {
      input.addEventListener('change', toggleProSection);
    });

    // Initialize on page load
    toggleProSection();
  });
</script>
@endsection
