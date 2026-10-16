<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Farm to Fork | Traçabilité Alimentaire & Éco-Responsabilité')</title>
  <meta name="description" content="@yield('description', 'Tracez vos produits de la ferme à l\'assiette. Découvrez l\'impact écologique, vérifiez les certifications et combattez le greenwashing.')">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- App CSS -->
  <link rel="stylesheet" href="{{ asset('frontend/css/app.css') }}">
  
  @yield('extra-css')
</head>
<body>

  @include('frontend.layouts.header')

  <main>
    @yield('content')
  </main>

  @include('frontend.layouts.footer')

  @yield('extra-js')
</body>
</html>
