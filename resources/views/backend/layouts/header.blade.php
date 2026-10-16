<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-top justify-content-center">
    <a class="navbar-brand brand-logo" href="{{ route('backend.dashboard') }}">
      <img src="{{ asset('backend/images/logo.svg') }}" alt="logo" /> 
    </a>
    <a class="navbar-brand brand-logo-mini" href="{{ route('backend.dashboard') }}">
      <img src="{{ asset('backend/images/logo-mini.svg') }}" alt="logo" /> 
    </a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-center">
    <ul class="navbar-nav">
      <li class="nav-item font-weight-semibold d-none d-lg-block">Farm to Fork - Back-Office</li>
    </ul>
    <form class="ml-auto search-form d-none d-md-block" action="#">
      <div class="form-group">
        <input type="search" class="form-control" placeholder="Rechercher...">
      </div>
    </form>
    <ul class="navbar-nav ml-auto">
      <!-- Logout Button -->
      <li class="nav-item">
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
          @csrf
          <button type="submit" class="btn btn-sm btn-danger" style="margin-right: 1rem; margin-top: 0.5rem;">
            <i class="mdi mdi-power"></i> Déconnexion
          </button>
        </form>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link count-indicator" id="notificationDropdown" href="#" data-toggle="dropdown">
          <i class="mdi mdi-bell-outline"></i>
          <span class="count bg-success">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="notificationDropdown">
          <a class="dropdown-item py-3 border-bottom">
            <p class="mb-0 font-weight-medium float-left">Vous avez 3 nouvelles notifications</p>
            <span class="badge badge-pill badge-primary float-right">Voir tout</span>
          </a>
          <a class="dropdown-item preview-item py-3">
            <div class="preview-thumbnail">
              <i class="mdi mdi-alert m-auto text-primary"></i>
            </div>
            <div class="preview-item-content">
              <h6 class="preview-subject font-weight-normal text-dark mb-1">Nouveau produit</h6>
              <p class="font-weight-light small-text mb-0">À l'instant</p>
            </div>
          </a>
        </div>
      </li>
      <li class="nav-item dropdown d-none d-xl-inline-block user-dropdown">
        <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
          <img class="img-xs rounded-circle" src="{{ asset('backend/images/faces/face8.jpg') }}" alt="Profile image"> 
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
          <div class="dropdown-header text-center">
            <img class="img-md rounded-circle" src="{{ asset('backend/images/faces/face8.jpg') }}" alt="Profile image">
            <p class="mb-1 mt-3 font-weight-semibold">Admin Farm to Fork</p>
            <p class="font-weight-light text-muted mb-0">admin@farmtofork.tn</p>
          </div>
          <a class="dropdown-item" href="#">Mon Profil <i class="dropdown-item-icon ti-dashboard"></i></a>
          <a class="dropdown-item" href="#">Paramètres <i class="dropdown-item-icon ti-settings"></i></a>
          <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="dropdown-item" style="border: none; background: none; cursor: pointer; text-align: left; width: 100%; padding: 0.75rem 1rem;">
              Déconnexion <i class="dropdown-item-icon ti-power-off"></i>
            </button>
          </form>
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="mdi mdi-menu"></span>
    </button>
  </div>
</nav>
