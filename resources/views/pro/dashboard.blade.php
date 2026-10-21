@extends('pro.layouts.processor')

@section('title', 'Tableau de Bord Transformateur - ' . $companyName . ' | Farm to Fork')

@section('processor-content')

<style>
  /* Navigation Tabs */
  .dashboard-nav {
    display: flex;
    gap: 0;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--gray-light);
  }
  .dashboard-nav a, .dashboard-nav button {
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    font-weight: 600;
    color: var(--gray);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all .3s;
    text-decoration: none;
  }
  .dashboard-nav a.active, .dashboard-nav button.active {
    color: var(--processor-primary);
    border-bottom-color: var(--processor-primary);
  }
  .dashboard-nav a:hover, .dashboard-nav button:hover {
    color: var(--processor-primary-dark);
  }

  /* KPI Cards Grid */
  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
  }

  .kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(45,106,79,.08);
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .kpi-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--processor-primary), rgba(45,106,79,.2));
    color: var(--processor-primary);
    font-size: 1.5rem;
    flex-shrink: 0;
  }

  .kpi-content {
    flex: 1;
  }

  .kpi-label {
    font-size: .82rem;
    color: var(--gray);
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 4px;
  }

  .kpi-value {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--dark);
  }

  /* Workflow Section */
  .workflow-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .workflow-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
    position: relative;
  }

  /* Connector between steps */
  .workflow-connector {
    position: absolute;
    top: 80px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--processor-primary) 20%, var(--processor-primary) 80%, transparent);
    display: none;
  }

  @media (min-width: 1200px) {
    .workflow-connector { display: block; }
    .workflow-container { gap: 1rem; }
  }

  .step-card {
    background: #fff;
    border-radius: 14px;
    padding: 2rem;
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(45,106,79,.1);
    display: flex;
    flex-direction: column;
    position: relative;
    transition: all .3s;
  }

  .step-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
  }

  .step-number {
    position: absolute;
    top: -12px;
    left: 20px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--processor-primary), var(--processor-primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(45,106,79,.25);
  }

  .step-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    margin-top: 1.5rem;
  }

  .step-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
  }

  .step-description {
    font-size: .92rem;
    color: var(--gray-dark);
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex: 1;
  }

  .step-button {
    background: var(--processor-primary);
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all .3s;
    align-self: flex-start;
  }

  .step-button:hover {
    background: var(--processor-primary-dark);
    color: white;
    text-decoration: none;
    transform: translateX(4px);
  }

  .step-badge {
    display: inline-block;
    background: #f0f9f4;
    color: var(--processor-primary);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: .75rem;
    font-weight: 700;
    margin-top: 1rem;
  }

  /* Stats Section */
  .stats-section {
    background: #fff;
    border-radius: 14px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(45,106,79,.08);
    margin-top: 3rem;
  }

  .stats-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1.5rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
  }

  .stat-item {
    text-align: center;
    padding: 1rem;
  }

  .stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--processor-primary);
    margin-bottom: 4px;
  }

  .stat-label {
    font-size: .85rem;
    color: var(--gray);
    font-weight: 600;
  }

  /* Page Title */
  .dashboard-title {
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
  }

  .dashboard-subtitle {
    margin-bottom: 2rem;
    font-size: .95rem;
    color: var(--gray);
    line-height: 1.5;
  }

  @media (max-width: 768px) {
    .hero-content { flex-direction: column; }
    .hero-title h1 { font-size: 1.6rem; }
    .workflow-container { grid-template-columns: 1fr; }
    .workflow-connector { display: none; }
    .dashboard-nav { flex-wrap: wrap; }
  }
</style>

<!-- Hero Banner -->
<div class="container" style="padding-top: 2rem; padding-bottom: 1.5rem;">
  <h1 class="dashboard-title">Tableau de Bord — Usine de Transformation</h1>
  <p class="dashboard-subtitle">Vue d'ensemble de votre activité et suivi de la chaîne de transformation.</p>
</div>

<!-- Main Dashboard Content -->
<div class="container" style="padding-bottom: 3rem;">
  <!-- Navigation Tabs -->
  <div class="dashboard-nav">
    <button class="active"><i class="fa-solid fa-chart-line"></i> Tableau de Bord</button>
    <a href="{{ route('pro.processor.raw-materials.index') }}"><i class="fa-solid fa-inbox"></i> Réceptions</a>
    <a href="{{ route('pro.processor.recipes.index') }}"><i class="fa-solid fa-gears"></i> Recettes</a>
    <a href="#"><i class="fa-solid fa-box"></i> Production</a>
  </div>

  @if(session('error'))
    <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
  @endif

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa-solid fa-hourglass-half"></i></div>
      <div class="kpi-content">
        <div class="kpi-label">Lots à Réceptionner</div>
        <div class="kpi-value">{{ $dashboardKpis['pending_harvests'] ?? 0 }}</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa-solid fa-book"></i></div>
      <div class="kpi-content">
        <div class="kpi-label">Recettes Actives</div>
        <div class="kpi-value">{{ $dashboardKpis['active_recipes'] ?? 0 }}</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa-solid fa-industry"></i></div>
      <div class="kpi-content">
        <div class="kpi-label">Production du Mois</div>
        <div class="kpi-value">0</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon"><i class="fa-solid fa-leaf"></i></div>
      <div class="kpi-content">
        <div class="kpi-label">Éco-Score</div>
        <div class="kpi-value">—</div>
      </div>
    </div>
  </div>

  <!-- Workflow Section -->
  <div style="margin-top: 3rem;">
    <div class="workflow-title">
      <i class="fa-solid fa-arrow-right"></i> Processus de Transformation
    </div>

    <div class="workflow-container">
      <div class="workflow-connector"></div>

      <!-- Step 1: Réception -->
      <div class="step-card">
        <span class="step-number">1</span>
        <div class="step-icon">📥</div>
        <h3 class="step-title">Réception</h3>
        <p class="step-description">
          Réceptionnez et validez les matières brutes reçues des producteurs. Enregistrez les quantités, qualités et conditions de stockage.
        </p>
        <a href="{{ route('pro.processor.raw-materials.index') }}" class="step-button">
          <i class="fa-solid fa-arrow-right"></i> Accéder
        </a>
        <span class="step-badge"><i class="fa-solid fa-check"></i> Étape 1</span>
      </div>

      <!-- Step 2: Formules & Recettes -->
      <div class="step-card">
        <span class="step-number">2</span>
        <div class="step-icon">🧪</div>
        <h3 class="step-title">Formules & Recettes</h3>
        <p class="step-description">
          Créez vos recettes et associez les ingrédients issus des réceptions. Définissez les ratios, les étapes de transformation et les rendements.
        </p>
        <a href="{{ route('pro.processor.recipes.index') }}" class="step-button">
          <i class="fa-solid fa-arrow-right"></i> Accéder
        </a>
        <span class="step-badge"><i class="fa-solid fa-check"></i> Étape 2</span>
      </div>

      <!-- Step 3: Production & Traçabilité -->
      <div class="step-card">
        <span class="step-number">3</span>
        <div class="step-icon">🏭</div>
        <h3 class="step-title">Production & Traçabilité</h3>
        <p class="step-description">
          Lancez des lots de fabrication, déduisez les stocks, gérez les emballages et générez les QR codes de traçabilité pour chaque produit.
        </p>
        <button class="step-button" disabled title="Fonctionnalité à venir" style="opacity: .6; cursor: not-allowed;">
          <i class="fa-solid fa-lock"></i> À venir
        </button>
        <span class="step-badge"><i class="fa-solid fa-hourglass"></i> Étape 3</span>
      </div>
    </div>
  </div>

  <!-- Stats Summary -->
  <div class="stats-section">
    <h3 class="stats-title"><i class="fa-solid fa-chart-bar"></i> Résumé d'Activité</h3>
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-value">{{ $dashboardKpis['total_receptions'] ?? 0 }}</div>
        <div class="stat-label">Réceptions Totales</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">{{ $dashboardKpis['total_recipes'] ?? 0 }}</div>
        <div class="stat-label">Recettes Créées</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">0</div>
        <div class="stat-label">Lots en Production</div>
      </div>
      <div class="stat-item">
        <div class="stat-value">0</div>
        <div class="stat-label">Produits Finis</div>
      </div>
    </div>
  </div>
</div>

@endsection
