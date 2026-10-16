# 📁 Structure des Vues - Farm to Fork

## 🏗️ Architecture Générale

```
resources/views/
│
├── frontend/                    # 🌐 Front-Office (Site Public)
│   ├── layouts/
│   │   ├── app.blade.php       # Layout principal Frontend
│   │   ├── header.blade.php    # Header réutilisable
│   │   └── footer.blade.php    # Footer réutilisable
│   │
│   └── pages/
│       ├── home.blade.php      # Page d'accueil
│       ├── about.blade.php     # À propos (TODO)
│       ├── products.blade.php  # Produits (TODO)
│       └── contact.blade.php   # Contact (TODO)
│
├── backend/                     # ⚙️ Back-Office (Admin)
│   ├── layouts/
│   │   ├── app.blade.php       # Layout principal Backend
│   │   ├── header.blade.php    # Header Admin
│   │   ├── sidebar.blade.php   # Sidebar Navigation
│   │   └── footer.blade.php    # Footer Admin
│   │
│   ├── pages/
│   │   ├── dashboard.blade.php # Dashboard Admin
│   │   ├── products/           # Gestion Produits (TODO)
│   │   ├── categories/         # Gestion Catégories (TODO)
│   │   ├── farms/              # Gestion Fermes (TODO)
│   │   ├── batches/            # Gestion Lots (TODO)
│   │   ├── history/            # Historique Chaîne (TODO)
│   │   ├── certifications/     # Gestion Certificats (TODO)
│   │   ├── labels/             # Vérification Labels (TODO)
│   │   ├── greenwashing/       # Anti-Greenwashing (TODO)
│   │   └── ecoscore/           # Gestion Éco-Score (TODO)
│   │
│   └── components/             # Composants réutilisables
│       ├── forms/              # (TODO)
│       ├── tables/             # (TODO)
│       └── modals/             # (TODO)
│
└── welcome.blade.php           # (Ancienne page - À nettoyer)
```

---

## 📍 Routing Structure

### Frontend Routes
```
/                    → frontend.pages.home
/about              → frontend.pages.about (TODO)
/products           → frontend.pages.products (TODO)
/contact            → frontend.pages.contact (TODO)
```

### Backend Routes
```
/admin/dashboard              → backend.pages.dashboard
/admin/products               → backend.pages.products.index (TODO)
/admin/products/create        → backend.pages.products.create (TODO)
/admin/categories             → backend.pages.categories.index (TODO)
/admin/farms                  → backend.pages.farms.index (TODO)
/admin/farms/create           → backend.pages.farms.create (TODO)
/admin/batches                → backend.pages.batches.index (TODO)
/admin/history                → backend.pages.history.index (TODO)
/admin/certifications         → backend.pages.certifications.index (TODO)
/admin/labels                 → backend.pages.labels.index (TODO)
/admin/greenwashing           → backend.pages.greenwashing.index (TODO)
/admin/ecoscore               → backend.pages.ecoscore.index (TODO)
```

---

## 🎨 Assets

### Frontend
- CSS: `public/frontend/css/app.css`
- Images: `public/frontend/images/`
- Fonts: `public/frontend/fonts/` (si applicable)

### Backend
- CSS: `public/backend/css/demo_1/style.css`
- JS: `public/backend/js/demo_1/dashboard.js`
- Images: `public/backend/images/`
- Vendors: `public/backend/vendors/`

---

## 🔄 Usage des Routes

### Frontend - Créer un lien
```blade
<a href="{{ route('frontend.home') }}">Accueil</a>
<a href="{{ route('frontend.about') }}">À Propos</a>
<a href="{{ route('frontend.products') }}">Produits</a>
<a href="{{ route('frontend.contact') }}">Contact</a>
```

### Backend - Créer un lien
```blade
<a href="{{ route('backend.dashboard') }}">Dashboard</a>
<a href="{{ route('backend.products.index') }}">Produits</a>
<a href="{{ route('backend.products.create') }}">Ajouter Produit</a>
<a href="{{ route('backend.farms.index') }}">Fermes</a>
```

---

## ✅ Checklist des Pages à Créer

### Frontend Pages (TO DO)
- [ ] about.blade.php
- [ ] products.blade.php (avec filtrage & search)
- [ ] contact.blade.php

### Backend Pages (TO DO)
- [ ] products/index.blade.php (liste)
- [ ] products/create.blade.php (formulaire)
- [ ] products/edit.blade.php
- [ ] products/show.blade.php
- [ ] categories/index.blade.php
- [ ] categories/create.blade.php
- [ ] farms/index.blade.php
- [ ] farms/create.blade.php
- [ ] farms/edit.blade.php
- [ ] batches/index.blade.php
- [ ] history/index.blade.php
- [ ] certifications/index.blade.php
- [ ] labels/index.blade.php
- [ ] greenwashing/index.blade.php
- [ ] ecoscore/index.blade.php

### Backend Components (TO DO)
- [ ] forms/product-form.blade.php
- [ ] forms/farm-form.blade.php
- [ ] forms/category-form.blade.php
- [ ] tables/products-table.blade.php
- [ ] tables/farms-table.blade.php
- [ ] modals/delete-confirm.blade.php

---

## 📝 Notes

- ✅ Structure Frontend/Backend séparée
- ✅ Layouts réutilisables
- ✅ Routes organisées par namespaces
- ✅ Assets statiques bien organisés
- ⚠️ Pages TODO à créer progressivement
- ⚠️ Composants réutilisables à développer
