<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Farm to Fork | Traçabilité Alimentaire & Éco-Responsabilité')</title>
  <meta name="description" content="@yield('description', 'Tracez vos produits de la ferme à l\'assiette. Découvrez l\'impact écologique, vérifiez les certifications et combattez le greenwashing.')">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- App CSS -->
  <link rel="stylesheet" href="{{ asset('frontend/css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('frontend/css/navbar.css') }}">

  @yield('extra-css')
</head>
<body>

  @include('frontend.layouts.header')

  @if(session('welcome'))
    <div class="ftn-toast" role="status">
      <i class="fa-solid fa-circle-check"></i>
      <div><strong>{{ session('welcome') }}</strong><small>Vous êtes connecté à Farm to Fork.</small></div>
    </div>
  @endif

  <main>
    @yield('content')
  </main>

  @include('frontend.layouts.footer')

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  @yield('extra-js')
</body>
</html>
