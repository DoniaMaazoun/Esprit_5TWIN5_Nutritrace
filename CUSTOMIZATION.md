# 🎨 Customization - Farm to Fork Back-Office

## Sidebar Styling - Vert Éco-Responsable

### Changements Appliqués

La sidebar du Back-Office a été transformée de **bleu** à des **tons verts éco-responsables** alignés avec la thématique Farm to Fork.

---

## 📁 Fichiers Modifiés

### 1. **Inline Style** - Dans le Layout Principal
- **Fichier**: `resources/views/backend/layouts/app.blade.php`
- **Type**: Bloc `<style>` dans le `<head>`
- **Avantage**: Applique le style directement et peut être customisé par page

```blade
<style>
  /* Farm to Fork Custom Sidebar Styling */
  .sidebar {
    background: linear-gradient(to top, #1e3a2b, #2d5a27) !important;
  }
  /* ... autres styles ... */
</style>
```

### 2. **External CSS** - Fichier Séparé (Recommandé)
- **Fichier**: `public/backend/css/farm-to-fork-custom.css`
- **Type**: Fichier CSS externe
- **Avantage**: Réutilisable, cacheable, meilleure performance
- **Lien dans le layout**: `<link rel="stylesheet" href="{{ asset('backend/css/farm-to-fork-custom.css') }}">`

---

## 🎨 Couleurs Utilisées

| Élément | Couleur | Code Hex |
|---------|---------|----------|
| Fond Sidebar (haut) | Vert Forêt Clair | `#2d5a27` |
| Fond Sidebar (bas) | Vert Forêt Profond | `#1e3a2b` |
| Lien Actif | Vert Éclatant | `#28a745` |
| Fond Hover | Vert Transparent | `rgba(40, 167, 69, 0.2)` |
| Accent (alternatif) | Orange | `#ff7a59` |

### Variables CSS (pour future utilisation)

```css
:root {
  --ftf-green-dark: #1e3a2b;      /* Vert forêt profond */
  --ftf-green-medium: #2d5a27;    /* Vert moyen */
  --ftf-green-light: #28a745;     /* Vert clair (actif) */
  --ftf-accent: #ff7a59;          /* Orange (alternatif) */
}
```

---

## 🎯 Style Appliqué à la Sidebar

### Sidebar Background
```css
.sidebar {
  background: linear-gradient(to top, #1e3a2b, #2d5a27) !important;
}
```
- Gradient du vert forêt profond (#1e3a2b) vers le vert moyen (#2d5a27)
- Donne une profondeur naturelle et écologique

### Links (Normal State)
```css
.sidebar > .nav .nav-item .nav-link {
  color: rgba(255, 255, 255, 0.85);
  transition: all 0.3s ease;
}
```
- Texte blanc semi-transparent pour une meilleure lisibilité
- Transition fluide pour les changements

### Links (Hover State)
```css
.sidebar > .nav .nav-item .nav-link:hover {
  color: #fff;
  background: rgba(40, 167, 69, 0.15) !important;
}
```
- Texte blanc opaque
- Fond vert transparent
- Invite à cliquer

### Active Link
```css
.sidebar > .nav .nav-item.active > .nav-link {
  color: #28a745 !important;
  background: rgba(40, 167, 69, 0.2) !important;
  border-left: 4px solid #28a745;
  padding-left: 51px;
}
```
- Texte en vert éclatant
- Fond vert transparent plus foncé
- Bordure gauche verte (4px) pour distinction
- Padding ajusté pour la bordure

### Sub-Menu Markers
```css
.sidebar > .nav .nav-item:not(.hover-open) .collapse .sub-menu .nav-item .nav-link:before {
  background: #28a745 !important;
}
```
- Ligne verticale verte au lieu de bleu
- Indique la hiérarchie du menu

### Profile Section
```css
.sidebar > .nav .nav-item.nav-profile .nav-link {
  background: rgba(40, 167, 69, 0.15) !important;
  border: 1px solid rgba(40, 167, 69, 0.3) !important;
}
```
- Légère teinte verte pour différencier le profil

---

## 🚀 Comment Utiliser

### 1. **Style Inline** (Déjà Appliqué)
Le style inline est appliqué directement dans `resources/views/backend/layouts/app.blade.php`:
```blade
<style>
  /* ... styles Farm to Fork ... */
</style>
```

### 2. **CSS Externe** (Recommandé pour Production)
Le fichier `public/backend/css/farm-to-fork-custom.css` est automatiquement inclus:
```blade
<link rel="stylesheet" href="{{ asset('backend/css/farm-to-fork-custom.css') }}">
```

---

## 🔧 Comment Modifier les Couleurs

### Option 1: Modifier le fichier CSS externe
```bash
# Éditer ce fichier
public/backend/css/farm-to-fork-custom.css

# Chercher et remplacer:
#28a745 → Nouvelle couleur verte claire
#1e3a2b → Nouvelle couleur verte foncée
#2d5a27 → Nouvelle couleur verte moyenne
```

### Option 2: Modifier le style inline
```blade
# Fichier: resources/views/backend/layouts/app.blade.php
# Éditer le bloc <style> dans le <head>
```

### Option 3: Utiliser les variables CSS
```css
/* Dans farm-to-fork-custom.css */
.sidebar {
  background: linear-gradient(to top, var(--ftf-green-dark), var(--ftf-green-medium)) !important;
}
```

---

## 📋 Checklist des Éléments Stylisés

- ✅ Background sidebar (gradient vert)
- ✅ Texte des liens (blanc semi-transparent)
- ✅ Hover state (fond vert transparent)
- ✅ Active link (texte vert + bordure)
- ✅ Sub-menu markers (vertical line verte)
- ✅ Profile section (teinte verte)
- ✅ Category labels (bordure verte)
- ✅ Menu arrows (couleur adaptée)

---

## 🎨 Couleurs Supplémentaires Disponibles

### Bonus Styles dans `farm-to-fork-custom.css`

```css
/* Boutons */
.btn-farm-primary { }        /* Boutons style Farm to Fork */
.btn-farm-primary:hover { }

/* Badges */
.badge-farm { }              /* Badges verts */

/* Texte */
.text-farm-green { }         /* Texte vert */

/* Arrière-plans */
.bg-farm-light { }           /* Fond vert clair transparent */
```

---

## 📱 Responsive Design

Les styles appliqués sont responsive et respectent:
- Desktop (largeur sidebar: 270px)
- Tablet/Mobile (sidebar off-canvas)
- Toutes les transitions sont fluides

---

## 🔄 Notes de Performance

- ✅ `!important` utilisé judicieusement pour surcharger le template original
- ✅ Transitions CSS optimisées (0.3s ease)
- ✅ Pas d'animations lourdes
- ✅ CSS minimal, pas de dépendances supplémentaires

---

## 📞 Support

Pour modifier d'autres éléments du Back-Office:
- Consulter `public/backend/css/demo_1/style.css` pour voir tous les sélecteurs disponibles
- Utiliser les mêmes variables de couleur pour la cohérence
- Tester sur tous les navigateurs

---

**Dernière modification**: {{ now()->format('d/m/Y H:i') }}
**Version**: Farm to Fork v1.0 - Custom Theme
