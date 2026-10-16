@extends('frontend.layouts.app')

@section('title', 'Accueil - Farm to Fork | Traçabilité Alimentaire')
@section('description', 'De la ferme à l\'assiette : Tracez vos produits en toute transparence. Découvrez l\'impact écologique et les certifications.')

@section('content')

<!-- Hero -->
<section class="hero hero--home">
  <div class="container hero-grid">
    <div class="hero-content">
      <span class="section-label">Transparence Alimentaire</span>
      <h1>De la Ferme à l'Assiette : <span>Tracez vos Produits en Toute Transparence</span></h1>
      <p class="hero-subtitle">Découvrez le parcours complet de vos aliments, consultez leur éco-score, vérifiez les certifications et luttez contre le greenwashing. Une alimentation consciente commence ici.</p>
      <div class="hero-actions">
        <a href="#" class="btn btn--primary btn--lg">Scanner un Produit</a>
        <a href="{{ route('frontend.products') }}" class="btn btn--outline">Explorer les Fermes</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-image-placeholder" style="background: url('{{ asset('frontend/images/placeholder.jpg') }}') center/cover no-repeat;">
        <i class="fa-solid fa-leaf" style="display:none;"></i>
        <span style="display:none;">Farm to Fork</span>
      </div>
      <div class="hero-badge">
        <i class="fa-solid fa-leaf"></i>
        <div class="hero-badge-text">
          100% Traçable
          <span>+5000 produits vérifiés</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Services Overview -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Nos Piliers</span>
      <h2>Une Alimentation Transparente & Responsable</h2>
      <p>Farm to Fork s'appuie sur trois piliers pour transformer votre relation avec la nourriture.</p>
    </div>
    <div class="cards-grid">
      <div class="card">
        <div class="card-icon card-icon--green"><i class="fa-solid fa-link"></i></div>
        <h3>Traçabilité des Lots</h3>
        <p>Suivez chaque produit de l'agriculteur au distributeur. Consultez les informations complètes : origine, date de récolte, conditions de culture, et chaîne de distribution.</p>
        <a href="#" class="card-link">En savoir plus <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="card">
        <div class="card-icon card-icon--coral"><i class="fa-solid fa-leaf"></i></div>
        <h3>Empreinte Écologique & Éco-Score</h3>
        <p>Découvrez l'impact CO2 de chaque produit. Calculez votre empreinte carbone alimentaire et comparez les alternatives pour des choix plus écologiques.</p>
        <a href="#" class="card-link">En savoir plus <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="card">
        <div class="card-icon card-icon--peach"><i class="fa-solid fa-certificate"></i></div>
        <h3>Certifications & Labels</h3>
        <p>Vérifiez les certifications : Bio, Commerce Équitable, RSE. Luttez contre le greenwashing et identifiez les vrais produits responsables.</p>
        <a href="#" class="card-link">En savoir plus <i class="fa-solid fa-arrow-right"></i></a>
      </div>
    </div>
  </div>
</section>

<!-- For Whom -->
<section class="section section--sage">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Pour Qui ?</span>
      <h2>Farm to Fork pour Tous les Consommateurs Responsables</h2>
      <p>Que tu sois concerned par l'environnement ou simplement curieux, tu trouveras les réponses ici.</p>
    </div>
    <div class="help-grid">
      <div class="help-card">
        <div class="help-card-icon"><i class="fa-solid fa-earth-americas"></i></div>
        <h3>Consommateurs Éco-Conscients</h3>
        <p>Réduisez votre empreinte carbone alimentaire. Choisissez des produits avec un impact minimal sur l'environnement.</p>
      </div>
      <div class="help-card">
        <div class="help-card-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h3>Victimes du Greenwashing</h3>
        <p>Démasquez les fausses certifications. Identifiez les véritables produits durables versus le marketing trompeur.</p>
      </div>
      <div class="help-card">
        <div class="help-card-icon"><i class="fa-solid fa-seedling"></i></div>
        <h3>Producteurs & Agriculteurs</h3>
        <p>Mettez en avant vos produits. Connectez-vous directement avec les consommateurs qui valorisent votre travail.</p>
      </div>
      <div class="help-card">
        <div class="help-card-icon"><i class="fa-solid fa-store"></i></div>
        <h3>Distributeurs & Retailers</h3>
        <p>Transparence garantie. Certifiez vos produits et gagnez la confiance de vos clients.</p>
      </div>
    </div>
  </div>
</section>

<!-- How It Works -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Fonctionnement</span>
      <h2>Comment Ça Marche ?</h2>
      <p>Trois étapes simples pour tracer vos produits et faire des choix responsables.</p>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step-number">1</div>
        <h3>Scanner</h3>
        <p>Scannez le code-barres ou le QR code du produit avec l'application Farm to Fork pour accéder aux informations complètes.</p>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <h3>Découvrir</h3>
        <p>Consultez la traçabilité, l'éco-score, l'empreinte carbone et les certifications du produit en détail.</p>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <h3>Agir</h3>
        <p>Faites des choix éclairés. Comparez les alternatives, soutenez les producteurs responsables et réduisez votre impact.</p>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="section section--cream">
  <div class="container">
    <div class="section-header">
      <span class="section-label">Témoignages</span>
      <h2>Ce Que Disent Nos Utilisateurs</h2>
      <p>Des consommateurs, producteurs et distributeurs qui transforment leurs habitudes grâce à Farm to Fork.</p>
    </div>
    <div class="testimonials-grid">
      <div class="testimonial">
        <div class="testimonial-stars">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
        </div>
        <p class="testimonial-text">J'achète plus intelligemment grâce à Farm to Fork. Voir l'empreinte carbone et l'origine exacte de mes produits m'aide à faire des choix alignés avec mes valeurs. C'est vraiment transparent et rassurant.</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar">MC</div>
          <div class="testimonial-author-info">
            <strong>Marie C.</strong>
            <span>Consommatrice Éco-Consciente</span>
          </div>
        </div>
      </div>
      <div class="testimonial">
        <div class="testimonial-stars">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
        </div>
        <p class="testimonial-text">En tant qu'agriculteur, Farm to Fork me permet de montrer le travail derrière mes produits. Mes clients directs apprécient la traçabilité et je peux communiquer directement mon engagement pour la durabilité.</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar">JL</div>
          <div class="testimonial-author-info">
            <strong>Jean L.</strong>
            <span>Producteur Bio</span>
          </div>
        </div>
      </div>
      <div class="testimonial">
        <div class="testimonial-stars">
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
          <i class="fa-solid fa-star"></i>
        </div>
        <p class="testimonial-text">Farm to Fork nous a aidés à identifier et exposer le greenwashing dans notre chaîne de distribution. C'est un outil précieux pour garantir l'authenticité de nos certifications.</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar">SB</div>
          <div class="testimonial-author-info">
            <strong>Sophie B.</strong>
            <span>Responsable RSE Distribution</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats -->
<section class="section section--dark">
  <div class="container">
    <div class="stats-grid">
      <div class="stat">
        <div class="stat-number">5000+</div>
        <div class="stat-label">Produits Tracés</div>
      </div>
      <div class="stat">
        <div class="stat-number">120+</div>
        <div class="stat-label">Producteurs Partenaires</div>
      </div>
      <div class="stat">
        <div class="stat-number">50K+</div>
        <div class="stat-label">Utilisateurs Actifs</div>
      </div>
      <div class="stat">
        <div class="stat-number">100%</div>
        <div class="stat-label">Transparence</div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="container">
    <h2>Prêt à Changer Votre Façon de Consommer ?</h2>
    <p>Téléchargez l'app Farm to Fork et commencez à scanner vos produits. Transparence garantie, du producteur à votre assiette.</p>
    <a href="#" class="btn btn--white btn--lg">Télécharger l'App <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</section>

@endsection
