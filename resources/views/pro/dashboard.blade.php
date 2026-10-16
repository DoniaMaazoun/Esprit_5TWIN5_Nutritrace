@extends('frontend.layouts.app')

@section('title', 'Dashboard Professionnel - ' . $companyName . ' | Farm to Fork')

@section('extra-css')
<style>
  .pro-dashboard {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f9f7 0%, #e8f5f3 100%);
    padding-top: 80px;
    padding-bottom: 40px;
  }

  .pro-header {
    background: linear-gradient(135deg, #1e3a2b 0%, #2d5a27 100%);
    color: white;
    padding: 40px 0;
    margin-bottom: 40px;
    border-bottom: 5px solid #28a745;
  }

  .pro-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
  }

  .pro-header-info h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .pro-header-info p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0.25rem 0;
  }

  .pro-header-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
  }

  .pro-header-badge i {
    font-size: 1.5rem;
  }

  .pro-logout-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .pro-logout-btn:hover {
    background: #c82333;
    color: white;
    text-decoration: none;
  }

  .pro-modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
  }

  .module-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(30, 58, 43, 0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .module-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #28a745, #20c997);
  }

  .module-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(30, 58, 43, 0.2);
  }

  .module-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
  }

  .module-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: linear-gradient(135deg, #1e3a2b 0%, #2d5a27 100%);
    color: white;
  }

  .module-card-badge {
    background: #ffc107;
    color: #000;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
  }

  .module-card h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: #1e3a2b;
  }

  .module-card p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.95rem;
  }

  .module-card-link {
    display: inline-block;
    color: #28a745;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  .module-card-link:hover {
    color: #1e3a2b;
  }

  .pro-welcome {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(30, 58, 43, 0.1);
    margin-bottom: 2rem;
  }

  .pro-welcome h2 {
    color: #1e3a2b;
    margin-bottom: 0.5rem;
  }

  .pro-welcome p {
    color: #666;
    margin-bottom: 0;
  }

  @media (max-width: 768px) {
    .pro-header-content {
      flex-direction: column;
      text-align: center;
    }

    .pro-header-info h1 {
      font-size: 1.8rem;
    }

    .pro-modules-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
@endsection

@section('content')

<!-- Pro Header -->
<div class="pro-header">
  <div class="container">
    <div class="pro-header-content">
      <div class="pro-header-info">
        <h1><i class="fa-solid {{ $dashboardData['icon'] }}"></i> {{ $dashboardData['title'] }}</h1>
        <p><strong>Entreprise :</strong> {{ $companyName }}</p>
        <p><strong>Type :</strong> {{ $user->getProTypeLabel() }}</p>
        <p><strong>N° TVA :</strong> {{ $taxId }}</p>
      </div>
      <div style="display: flex; gap: 1rem; flex-direction: column;">
        <div class="pro-header-badge">
          <i class="fa-solid fa-check-circle"></i>
          <div>
            <div style="font-weight: 700;">Compte Approuvé</div>
            <small style="opacity: 0.8;">Accès complet</small>
          </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
          @csrf
          <button type="submit" class="pro-logout-btn">
            <i class="fa-solid fa-power-off"></i> Déconnexion
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Dashboard Content -->
<div class="pro-dashboard">
  <div class="container">
    <!-- Welcome Card -->
    <div class="pro-welcome">
      <h2>Bienvenue, {{ $user->name }} ! 👋</h2>
      <p>Accédez aux fonctionnalités de gestion de votre activité. Tous les modules ci-dessous sont en cours de développement et seront disponibles prochainement.</p>
    </div>

    <!-- Modules Grid -->
    <div class="pro-modules-grid">
      @foreach($dashboardData['modules'] as $module)
        <div class="module-card">
          <div class="module-card-header">
            <div class="module-card-icon">
              <i class="fa-solid {{ $module['icon'] }}"></i>
            </div>
            <span class="module-card-badge">{{ $module['badge'] }}</span>
          </div>
          <h3>{{ $module['title'] }}</h3>
          <p>{{ $module['description'] }}</p>
          <a href="{{ $module['route'] }}" class="module-card-link">
            Accéder au module <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      @endforeach
    </div>

    <!-- Info Section -->
    <div style="margin-top: 3rem; text-align: center;">
      <p style="color: #666; font-size: 0.95rem;">
        <i class="fa-solid fa-info-circle"></i>
        Les fonctionnalités sont actuellement en développement. Contactez notre équipe support pour toute question.
      </p>
    </div>
  </div>
</div>

@endsection
