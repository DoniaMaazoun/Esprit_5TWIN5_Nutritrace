{{-- Panneau visuel des pages d'authentification. Variables : $title (HTML autorisé : <em>), $lead --}}
@php
  $authStats = [
      'products' => \App\Models\Harvest::count(),
      'producers' => \App\Models\User::where('role', 'pro')->where('pro_type', 'producer')->where('status', 'approved')->count(),
      'farms' => \App\Models\Farm::count(),
  ];
@endphp
<aside class="auth-visual" style="--auth-image: url('{{ asset('frontend/images/hero-product.jpg') }}')">
  <div>
    <a href="{{ route('frontend.home') }}" class="auth-visual__brand"><span><i class="fa-solid fa-leaf"></i></span> Farm to Fork</a>
    <h2>{!! $title !!}</h2>
    <p class="lead">{{ $lead }}</p>
    <ul class="auth-benefits">
      <li><i class="fa-solid fa-qrcode"></i><div><strong>Traçabilité par QR code</strong><small>Chaque lot révèle son exploitation, sa parcelle et sa date de récolte.</small></div></li>
      <li><i class="fa-solid fa-shield-halved"></i><div><strong>Producteurs vérifiés</strong><small>Les comptes professionnels sont validés par notre équipe.</small></div></li>
      <li><i class="fa-solid fa-robot"></i><div><strong>Assistant AgriBot</strong><small>Conseils de saison et analyse de vos exploitations.</small></div></li>
    </ul>
  </div>
  <div class="auth-stats">
    <div><strong>{{ $authStats['products'] }}</strong><small>lots tracés</small></div>
    <div><strong>{{ $authStats['producers'] }}</strong><small>producteurs</small></div>
    <div><strong>{{ $authStats['farms'] }}</strong><small>exploitations</small></div>
  </div>
</aside>
