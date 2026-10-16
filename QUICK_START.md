# 🚀 Guide de Démarrage Rapide - Farm to Fork

## 🎯 Authentifiants de Test

### Admin
```
Email: admin@test.tn
Password: password123
Accès: /admin/dashboard
```

### Consumer (Consommateur)
```
Email: consumer@test.tn
Password: password123
Accès: Frontend complet
```

### Professional - Pending
```
Email: pro@test.tn
Password: password123
Statut: En attente d'approbation
Accès: AUCUN (essayer de se connecter → Erreur)
```

### Professional - Approved
```
Email: pro-approved@test.tn
Password: password123
Statut: Approuvé
Accès: Frontend complet
```

---

## 🌐 URLs Principales

### Frontend (Public)
- **Home:** `http://localhost:8000/`
- **Inscription:** `http://localhost:8000/register`
- **Connexion:** `http://localhost:8000/login`

### Backend (Protégé - Admin uniquement)
- **Dashboard:** `http://localhost:8000/admin/dashboard`
- **Produits:** `http://localhost:8000/admin/products`
- **Fermes:** `http://localhost:8000/admin/farms`
- **Catégories:** `http://localhost:8000/admin/categories`
- **Traçabilité:** `http://localhost:8000/admin/batches`
- **Certifications:** `http://localhost:8000/admin/certifications`

---

## 🔧 Setup

### 1. Cloner le projet
```bash
cd c:\Users\donia\Desktop\FarmToFork
```

### 2. Installer les dépendances
```bash
composer install
npm install
```

### 3. Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Base de données
```bash
php artisan migrate
php artisan db:seed
```

### 5. Lancer le serveur
```bash
php artisan serve
```

### 6. Compiler les assets (optionnel)
```bash
npm run dev
```

---

## 📋 Workflows à Tester

### ✅ Workflow 1: Inscription Consommateur
1. Aller à `/register`
2. Sélectionner "Consommateur"
3. Remplir le formulaire
4. Submit
5. **Résultat:** Redirection Home + Authentification automatique ✅

### ✅ Workflow 2: Inscription Professionnel
1. Aller à `/register`
2. Sélectionner "Professionnel"
3. Champs dynamiques apparaissent
4. Remplir tous les champs
5. Submit
6. **Résultat:** Redirection Login + Message d'attente ✅

### ✅ Workflow 3: Connexion Admin
1. Aller à `/login`
2. Email: `admin@test.tn`
3. Password: `password123`
4. Submit
5. **Résultat:** Redirection `/admin/dashboard` ✅

### ❌ Workflow 4: Pro En Attente (Devrait échouer)
1. Aller à `/login`
2. Email: `pro@test.tn`
3. Password: `password123`
4. Submit
5. **Résultat:** Erreur "Votre compte est en cours de vérification" ✅

### ✅ Workflow 5: Pro Approuvé
1. Aller à `/login`
2. Email: `pro-approved@test.tn`
3. Password: `password123`
4. Submit
5. **Résultat:** Redirection Home + Authentification ✅

---

## 🎨 Design

### Frontend
- Couleurs vertes Farm to Fork (#1e3a2b, #2d5a27, #28a745)
- Boutons "Connexion" et "S'inscrire" dans le header
- Responsive design
- Langue: Français

### Backend
- Navbar verte (gradient)
- Sidebar verte (gradient)
- Liens actifs en vert clair
- Responsive design
- Langue: Français

---

## 📁 Fichiers Importants

### Vues
- `resources/views/auth/login.blade.php` - Page login
- `resources/views/auth/register.blade.php` - Page inscription
- `resources/views/frontend/layouts/header.blade.php` - Header avec boutons auth
- `resources/views/backend/layouts/app.blade.php` - Layout backend

### Controllers
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - Login/Logout
- `app/Http/Controllers/Auth/RegisteredUserController.php` - Inscription

### Middleware
- `app/Http/Middleware/AdminMiddleware.php` - Protection des routes admin

### Routes
- `routes/web.php` - Toutes les routes

### Models
- `app/Models/User.php` - Modèle User avec helpers

### Migrations & Seeds
- `database/migrations/2026_10_16_205939_add_role_fields_to_users_table.php`
- `database/seeders/UserSeeder.php`

---

## 🐛 Troubleshooting

### "Page not found"
- Vérifier que le serveur Laravel est lancé
- Vérifier l'URL

### "CSRF token mismatch"
- Rafraîchir la page
- Vérifier les cookies

### "Column not found"
- Exécuter les migrations: `php artisan migrate`

### "Middleware not found"
- Vérifier `bootstrap/app.php`

### "Asset not found"
- Vérifier les chemins dans les vues
- Vérifier que les fichiers existent

---

## 📚 Documentation Complète

Pour plus de détails, consulter:
- `AUTHENTICATION.md` - Documentation complète
- `AUTH_COMMANDS.md` - Commandes à exécuter
- `AUTH_SETUP_SUMMARY.md` - Résumé du setup

---

## ✨ Features Implémentées

- ✅ Système de rôles (admin/pro/consumer)
- ✅ Système de statuts (active/pending/approved/rejected)
- ✅ Inscription consommateur (automatique active)
- ✅ Inscription professionnel (pending par défaut)
- ✅ Connexion avec vérification du statut
- ✅ Logout
- ✅ Middleware Admin
- ✅ Helper methods sur User
- ✅ Formulaires en français
- ✅ Design responsive
- ✅ Boutons auth dans le header
- ✅ Dynamic pro fields avec JavaScript

---

## 🎯 Prochaines étapes

- [ ] Dashboard Admin pour approver les comptes Pro
- [ ] Notifications par email
- [ ] Password reset
- [ ] Email verification
- [ ] Two-factor authentication
- [ ] Social login

---

**Branche:** `feature/authentication`
**Status:** ✅ READY TO TEST
**Date:** 2026-10-16
