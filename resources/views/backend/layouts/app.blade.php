<!DOCTYPE html>
<html lang="fr">
<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>@yield('title', 'Farm to Fork - Back-Office')</title>

  <!-- Vendor CSS -->
  <link rel="stylesheet" href="{{ asset('backend/vendors/iconfonts/mdi/css/materialdesignicons.min.css') }}">
  <link rel="stylesheet" href="{{ asset('backend/vendors/iconfonts/ionicons/dist/css/ionicons.css') }}">
  <link rel="stylesheet" href="{{ asset('backend/vendors/iconfonts/flag-icon-css/css/flag-icon.min.css') }}">
  <link rel="stylesheet" href="{{ asset('backend/vendors/css/vendor.bundle.base.css') }}">
  <link rel="stylesheet" href="{{ asset('backend/vendors/css/vendor.bundle.addons.css') }}">

  <!-- Shared CSS -->
  <link rel="stylesheet" href="{{ asset('backend/css/shared/style.css') }}">

  <!-- Layout CSS -->
  <link rel="stylesheet" href="{{ asset('backend/css/demo_1/style.css') }}">

  <!-- Favicon -->
  <link rel="shortcut icon" href="{{ asset('backend/images/favicon.ico') }}" />

  <!-- Farm to Fork Custom Styling - INLINE -->
  <style>
    /* NAVBAR GREEN COLORS */
    .navbar.default-layout {
      background: linear-gradient(to right, #1e3a2b, #2d5a27) !important;
    }
    
    .navbar.default-layout .navbar-brand-wrapper {
      background: linear-gradient(to bottom, #1e3a2b, #2d5a27) !important;
    }

    .navbar.default-layout .navbar-menu-wrapper {
      background: linear-gradient(to right, #1e3a2b, #2d5a27) !important;
    }

    .navbar.default-layout .navbar-menu-wrapper .navbar-nav .nav-item .nav-link {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .navbar.default-layout .navbar-menu-wrapper .navbar-nav .nav-item .nav-link:hover {
      color: #28a745 !important;
    }

    /* SIDEBAR GREEN COLORS */
    .sidebar {
      background: linear-gradient(to top, #1e3a2b, #2d5a27) !important;
    }

    .sidebar > .nav .nav-item .nav-link {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .sidebar > .nav .nav-item .nav-link:hover {
      color: #fff !important;
      background: rgba(40, 167, 69, 0.15) !important;
    }

    .sidebar > .nav .nav-item.active > .nav-link {
      color: #28a745 !important;
      background: rgba(40, 167, 69, 0.2) !important;
      border-left: 4px solid #28a745 !important;
      padding-left: 51px !important;
    }

    .sidebar > .nav .nav-item.active > .nav-link .menu-title,
    .sidebar > .nav .nav-item.active > .nav-link i {
      color: #28a745 !important;
    }
  </style>

  @yield('extra-css')
</head>
<body>
  <div class="container-scroller">
    
    <!-- Header -->
    @include('backend.layouts.header')

    <div class="container-fluid page-body-wrapper">
      
      <!-- Sidebar -->
      @include('backend.layouts.sidebar')

      <!-- Main Panel -->
      <div class="main-panel">
        <div class="content-wrapper">
          @yield('content')
        </div>

        <!-- Footer -->
        @include('backend.layouts.footer')
      </div>

    </div>

  </div>

  <!-- Vendor JS -->
  <script src="{{ asset('backend/vendors/js/vendor.bundle.base.js') }}"></script>
  <script src="{{ asset('backend/vendors/js/vendor.bundle.addons.js') }}"></script>

  <!-- Shared JS -->
  <script src="{{ asset('backend/js/shared/off-canvas.js') }}"></script>
  <script src="{{ asset('backend/js/shared/hoverable-collapse.js') }}"></script>
  <script src="{{ asset('backend/js/shared/template.js') }}"></script>

  <!-- Layout JS -->
  <script src="{{ asset('backend/js/demo_1/dashboard.js') }}"></script>

  @yield('extra-js')
</body>
</html>
