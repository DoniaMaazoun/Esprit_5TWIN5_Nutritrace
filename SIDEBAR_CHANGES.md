# 🎨 Sidebar - Changements de Couleur Farm to Fork

## 📌 Résumé Rapide

La sidebar du Back-Office est passée de **BLEU** à **VERT ÉCO-RESPONSABLE** aligné avec l'identité Farm to Fork.

---

## 🎯 Avant / Après

### AVANT (Original)
```
Background:      Bleu gradient (#5768f3 → #1c45ef)
Active Link:     Couleur standard
Hover:           Bleu foncé
```

### APRÈS (Farm to Fork)
```
Background:      Vert Forêt gradient (#2d5a27 → #1e3a2b)
Active Link:     Vert Éclatant (#28a745) + bordure verte
Hover:           Vert transparent + texte blanc
Profile:         Teinte verte
Sub-menus:       Marqueurs verts
```

---

## 💻 Code Exact Appliqué

### 1. **Fichier**: `resources/views/backend/layouts/app.blade.php`

#### Bloc `<style>` à ajouter dans le `<head>`:

```html
<!-- Farm to Fork Custom Sidebar Styling -->
<style>
  /* ==================== FARM TO FORK SIDEBAR - CUSTOM COLORS ==================== */
  
  /* Main Sidebar Background - Vert Forêt Profond */
  .sidebar {
    background: linear-gradient(to top, #1e3a2b, #2d5a27) !important;
  }

  /* Sidebar Links */
  .sidebar > .nav .nav-item .nav-link {
    color: rgba(255, 255, 255, 0.85);
    transition: all 0.3s ease;
  }

  /* Sidebar Links Hover */
  .sidebar > .nav .nav-item .nav-link:hover {
    color: #fff;
    background: rgba(40, 167, 69, 0.15) !important;
  }

  /* Sidebar Active Link - Vert Éclatant */
  .sidebar > .nav .nav-item.active > .nav-link {
    color: #28a745 !important;
    background: rgba(40, 167, 69, 0.2) !important;
    border-left: 4px solid #28a745;
    padding-left: 51px;
  }

  .sidebar > .nav .nav-item.active > .nav-link .menu-title,
  .sidebar > .nav .nav-item.active > .nav-link i {
    color: #28a745 !important;
  }

  /* Hover item dans le menu principal */
  .sidebar > .nav:not(.sub-menu) > .nav-item:hover:not(.nav-profile):not(.hover-open) > .nav-link:not([aria-expanded="true"]) {
    background: rgba(40, 167, 69, 0.2) !important;
    color: #fff;
  }

  /* Hover item indicator */
  .sidebar > .nav:not(.sub-menu) > .nav-item:hover:not(.nav-profile):not(.hover-open) > .nav-link:not([aria-expanded="true"]):before {
    border-color: #28a745 !important;
  }

  /* Sub-menu link marker color */
  .sidebar > .nav .nav-item:not(.hover-open) .collapse .sub-menu .nav-item .nav-link:before,
  .sidebar > .nav .nav-item:not(.hover-open) .collapsing .sub-menu .nav-item .nav-link:before {
    background: #28a745 !important;
  }

  /* Sub-menu hover */
  .sidebar > .nav .nav-item:not(.hover-open) .collapse .sub-menu .nav-item .nav-link:hover:before,
  .sidebar > .nav .nav-item:not(.hover-open) .collapsing .sub-menu .nav-item .nav-link:hover:before {
    background: #fff !important;
  }

  /* Profile Section */
  .sidebar > .nav .nav-item.nav-profile .nav-link {
    background: rgba(40, 167, 69, 0.15) !important;
    border: 1px solid rgba(40, 167, 69, 0.3) !important;
  }

  /* Category Label */
  .sidebar > .nav .nav-item.nav-category {
    color: rgba(255, 255, 255, 0.6);
    border-bottom: 1px solid rgba(40, 167, 69, 0.3) !important;
  }

  /* Menu Arrow Color */
  .sidebar > .nav .nav-item .nav-link i.menu-arrow:before {
    color: rgba(255, 255, 255, 0.6) !important;
  }

  /* Active menu arrow */
  .sidebar > .nav .nav-item.active .nav-link i.menu-arrow:before {
    color: #28a745 !important;
  }

  /* ==================== FIN CUSTOM SIDEBAR ==================== */
</style>
```

---

## 📁 Alternative: Fichier CSS Séparé

### 2. **Fichier**: `public/backend/css/farm-to-fork-custom.css`

Ce fichier contient le même CSS mais de manière organisée et réutilisable.

**À inclure dans le `<head>`**:
```html
<link rel="stylesheet" href="{{ asset('backend/css/farm-to-fork-custom.css') }}">
```

---

## 🎨 Détail des Couleurs

### Palette Farm to Fork

| Nom | Hex | Utilisation |
|-----|-----|-------------|
| Vert Forêt Profond | `#1e3a2b` | Haut du gradient sidebar |
| Vert Forêt Moyen | `#2d5a27` | Bas du gradient sidebar |
| Vert Éclatant | `#28a745` | Liens actifs, bordures |
| Vert Transparent 20% | `rgba(40, 167, 69, 0.2)` | Fond hover active |
| Vert Transparent 15% | `rgba(40, 167, 69, 0.15)` | Fond hover normal |
| Orange (Alternatif) | `#ff7a59` | Contraste optionnel |

---

## 📋 Éléments Modifiés

### Sidebar Background
- **De**: Gradient bleu (#5768f3 → #1c45ef)
- **À**: Gradient vert (#2d5a27 → #1e3a2b)

### Links
- **Normal**: Blanc semi-transparent (0.85)
- **Hover**: Fond vert + texte blanc opaque
- **Active**: Vert éclatant + bordure gauche 4px

### Sub-menus
- **Marqueur**: Ligne verticale verte (#28a745)

### Profile Section
- **Background**: Vert semi-transparent
- **Border**: Vert semi-transparent

### Category Labels
- **Border-bottom**: Ligne verte

---

## 🔧 Comment Modifier

### Changer la couleur verte des liens actifs

**Remplacer**: `#28a745`
**Par**: Votre nouvelle couleur

Exemple: Utiliser l'orange pour le contraste
```css
.sidebar > .nav .nav-item.active > .nav-link {
  color: #ff7a59 !important;  /* Orange au lieu de vert */
  border-left: 4px solid #ff7a59;
}
```

### Changer le gradient du background

**Remplacer**: `linear-gradient(to top, #1e3a2b, #2d5a27)`
**Par**: Vos nouvelles couleurs

Exemple: Gradient plus prononcé
```css
.sidebar {
  background: linear-gradient(to top, #0f2818, #3d7a3a) !important;
}
```

---

## ✅ Status

- ✅ Styles appliqués
- ✅ Fichiers créés
- ✅ Documentation complète
- ✅ Prêt pour la production
- ✅ Facile à modifier

---

## 📖 Fichiers de Référence

- `resources/views/backend/layouts/app.blade.php` - Inclut le style inline
- `public/backend/css/farm-to-fork-custom.css` - Fichier CSS externe (recommandé)
- `CUSTOMIZATION.md` - Guide complet
- `STRUCTURE.md` - Architecture du projet
- `public/backend/css/demo_1/style.css` - CSS original (ne pas modifier)

---

## 🎨 Variables CSS Disponibles

```css
:root {
  --ftf-green-dark: #1e3a2b;      /* Vert forêt profond */
  --ftf-green-medium: #2d5a27;    /* Vert moyen */
  --ftf-green-light: #28a745;     /* Vert clair (actif) */
  --ftf-accent: #ff7a59;          /* Orange (alternatif) */
}
```

**Utilisation dans d'autres fichiers**:
```css
.mon-element {
  color: var(--ftf-green-light);
  background: var(--ftf-green-dark);
}
```

---

## 🚀 Résultat Final

La sidebar est maintenant **100% alignée** avec l'identité écologique et responsable de Farm to Fork! 🌿

Visitez `/admin/dashboard` pour voir le résultat en action.

---

**Date**: Octobre 2026
**Version**: Farm to Fork v1.0
**Status**: ✅ Déployé
