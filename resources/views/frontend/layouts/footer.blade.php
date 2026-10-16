<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="{{ route('frontend.home') }}" class="logo">
          <div class="logo-icon"><i class="fa-solid fa-leaf"></i></div>
          <div class="logo-text" style="color:#fff;">Farm to Fork<span>Traçabilité</span></div>
        </a>
        <p>De la ferme à l'assiette. Transparence alimentaire, éco-responsabilité et lutte contre le greenwashing. Ensemble, transformons notre façon de consommer.</p>
        <div class="footer-social">
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
        </div>
      </div>
      <div class="footer-links">
        <h4>Navigation</h4>
        <a href="{{ route('frontend.home') }}">Accueil</a>
        <a href="{{ route('frontend.about') }}">À Propos</a>
        <a href="{{ route('frontend.products') }}">Produits</a>
        <a href="{{ route('frontend.contact') }}">Contact</a>
      </div>
      <div class="footer-links">
        <h4>Ressources</h4>
        <a href="#">Blog</a>
        <a href="#">Guide Éco-Score</a>
        <a href="#">Certifications</a>
        <a href="#">FAQ</a>
      </div>
      <div>
        <h4>Contact</h4>
        <div class="footer-contact-item">
          <i class="fa-solid fa-location-dot"></i>
          <span>Tunis, Tunisia</span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-phone"></i>
          <span>+216 XX XXX XXX</span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-envelope"></i>
          <span>contact@farmtofork.tn</span>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; {{ date('Y') }} Farm to Fork. Tous droits réservés.</p>
      <p><a href="#">Politique de confidentialité</a> | <a href="#">Conditions d'utilisation</a></p>
    </div>
  </div>
</footer>
