# 🔐 Documentation - Système d'Authentification Farm to Fork

## 📋 Table des matières
1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Base de données](#base-de-données)
4. [Controllers](#controllers)
5. [Routes](#routes)
6. [Vues](#vues)
7. [Middleware](#middleware)
8. [Modèle User](#modèle-user)
9. [Workflows](#workflows)
10. [Commandes Artisan](#commandes-artisan)

---

## Vue d'ensemble

Le système d'authentification de **Farm to Fork** est basé sur **Laravel Breeze** avec une configuration personnalisée pour gérer 3 rôles métiers :

| Rôle | Type | Statut Initial | Accès |
|------|------|-----------------|--------|
| **consumer** | Consommateur (Grand public) | `active` | Frontend |
| **pro** | Professionnel (Producteur/Transformateur/Distributeur) | `pending` | Frontend (après approbation) |
| **admin** | Administrateur | `active` | Backend & Dashboard |

---

## Architecture

### Flux d'inscription

#### Consommateur
```
Inscription (Consumer) 
    ↓
Validation formulaire
    ↓
Création User (role='consumer', status='active')
    ↓
Authentification automatique
    ↓
Redirection → Frontend Home
```

#### Professionnel
```
Inscription (Pro) 
    ↓
Validation formulaire + champs pro
    ↓
Création User (role='pro', status='pending')
    ↓
Redirection → Login avec message d'attente
    ↓
Email de validation envoyé à l'admin
    ↓
Admin approuve/rejette via le back-office
    ↓
Utilisateur notifié par email
```

### Flux de connexion

```
Credentials (email + password)
    ↓
Recherche User
    ↓
Vérification mot de passe
    ↓
Vérification statut (si Pro)
    ├─ En attente (pending) → Erreur "En cours de vérification"
    ├─ Rejeté (rejected) → Erreur "Compte refusé"
    └─ Approuvé (approved) → Continue
    ↓
Régénération session
    ↓
Redirection basée sur le rôle
    ├─ Admin → /admin/dashboard
    └─ Autre → / (Frontend)
```

---

## Base de données

### Migration : `add_role_fields_to_users_table`

Ajoute 5 colonnes à la table `users` :

```sql
-- Type de compte
ALTER TABLE users ADD role VARCHAR(20) DEFAULT 'consumer';

-- Type d'activité (pour les pros)
ALTER TABLE users ADD pro_type VARCHAR(50) NULLABLE;
  -- Valeurs: 'producer', 'processor', 'distributor'

-- Nom de l'établissement/ferme
ALTER TABLE users ADD company_name VARCHAR(255) NULLABLE;

-- Identifiant fiscal / Matricule
ALTER TABLE users ADD tax_id VARCHAR(255) NULLABLE UNIQUE;

-- Statut du compte
ALTER TABLE users ADD status VARCHAR(20) DEFAULT 'active';
  -- Valeurs: 'active', 'pending', 'approved', 'rejected'
```

### Schéma complet de la table users

| Colonne | Type | Nullable | Default | Description |
|---------|------|----------|---------|-------------|
| id | BIGINT | ❌ | | ID unique |
| name | VARCHAR(255) | ❌ | | Nom complet |
| email | VARCHAR(255) | ❌ | | Email unique |
| email_verified_at | TIMESTAMP | ✅ | NULL | Vérification email |
| password | VARCHAR(255) | ❌ | | Mot de passe hashé |
| remember_token | VARCHAR(100) | ✅ | NULL | Token "Se souvenir de moi" |
| role | VARCHAR(20) | ❌ | consumer | Rôle (admin, pro, consumer) |
| pro_type | VARCHAR(50) | ✅ | NULL | Type pro (producer, processor, distributor) |
| company_name | VARCHAR(255) | ✅ | NULL | Nom ferme/entreprise |
| tax_id | VARCHAR(255) | ✅ | NULL | Identifiant fiscal UNIQUE |
| status | VARCHAR(20) | ❌ | active | Statut (active, pending, approved, rejected) |
| created_at | TIMESTAMP | ❌ | | Date création |
| updated_at | TIMESTAMP | ❌ | | Date modification |

---

## Controllers

### `RegisteredUserController`

**Emplacement:** `app/Http/Controllers/Auth/RegisteredUserController.php`

**Méthodes:**

#### `create(): View`
Affiche la vue d'inscription avec sélection du type de compte.

```php
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
```

#### `store(Request $request): RedirectResponse`
Traite l'inscription et crée le nouvel utilisateur.

**Validation:**
- Tous les comptes: name, email, password, password_confirmation, terms
- Comptes Pro uniquement: pro_type, company_name, tax_id

**Logique:**
- Si Consumer → Création + Auth automatique + Redirection Home
- Si Pro → Création + Status 'pending' + Redirection Login + Message d'attente

### `AuthenticatedSessionController`

**Emplacement:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**Méthodes:**

#### `create(): View`
Affiche la vue de connexion.

```php
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
```

#### `store(Request $request): RedirectResponse`
Traite la connexion et authentifie l'utilisateur.

**Validations:**
- email (requis, format email)
- password (requis)

**Vérifications:**
1. Tentative d'authentification
2. Si Pro + Status 'pending' → Logout + Erreur "En attente de vérification"
3. Si succès → Régénération session + Redirection basée sur rôle

#### `destroy(Request $request): RedirectResponse`
Déconnecte l'utilisateur et invalide la session.

```php
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
```

---

## Routes

### Routes d'Authentification (Middleware: `guest`)

```php
// Inscription
GET    /register          → RegisteredUserController@create    [name: 'register']
POST   /register          → RegisteredUserController@store

// Connexion
GET    /login             → AuthenticatedSessionController@create [name: 'login']
POST   /login             → AuthenticatedSessionController@store

// Déconnexion (Middleware: auth)
POST   /logout            → AuthenticatedSessionController@destroy [name: 'logout']
```

### Routes Frontend (Middleware: `web`)

```php
GET    /                  → Home Page              [name: 'frontend.home']
GET    /about             → About Page             [name: 'frontend.about']
GET    /products          → Products Page          [name: 'frontend.products']
GET    /contact           → Contact Page           [name: 'frontend.contact']
```

### Routes Backend (Middleware: `auth`, `admin`)

```php
GET    /admin/dashboard   → Dashboard              [name: 'backend.dashboard']

GET    /admin/products                             [name: 'backend.products.index']
GET    /admin/products/create                      [name: 'backend.products.create']

GET    /admin/categories                           [name: 'backend.categories.index']
GET    /admin/farms                                [name: 'backend.farms.index']
GET    /admin/farms/create                         [name: 'backend.farms.create']
GET    /admin/batches                              [name: 'backend.batches.index']
GET    /admin/history                              [name: 'backend.history.index']
GET    /admin/certifications                       [name: 'backend.certifications.index']
GET    /admin/labels                               [name: 'backend.labels.index']
GET    /admin/greenwashing                         [name: 'backend.greenwashing.index']
GET    /admin/ecoscore                             [name: 'backend.ecoscore.index']
```

---

## Vues

### Login View
**Emplacement:** `resources/views/auth/login.blade.php`

**Éléments:**
- Logo & titre
- Formulaire avec email & password
- Checkbox "Se souvenir de moi"
- Lien "Mot de passe oublié?"
- Lien "S'inscrire"
- Gestion des erreurs (identifiants invalides, compte en attente)
- Thème vert Farm to Fork

### Register View
**Emplacement:** `resources/views/auth/register.blade.php`

**Éléments:**
- Logo & titre
- Sélection dynamique du type de compte (Consommateur / Professionnel)
- Formulaire de base (name, email, password)
- Champs dynamiques Pro (pro_type, company_name, tax_id)
- Checkbox conditions d'utilisation
- JavaScript pour afficher/masquer les champs Pro
- Thème vert Farm to Fork

**JavaScript interactif:**
```javascript
// Affiche les champs Pro quand on sélectionne le rôle 'pro'
// Masque les champs Pro quand on sélectionne 'consumer'
// Valide les champs Pro si nécessaire
```

---

## Middleware

### `AdminMiddleware`

**Emplacement:** `app/Http/Middleware/AdminMiddleware.php`

**Vérifications:**
1. L'utilisateur est authentifié
2. L'utilisateur a le rôle 'admin'

**Comportement:**
- ✅ OK → Accès au route protégée
- ❌ NOK → Erreur 403 "Accès refusé. Seuls les administrateurs peuvent accéder à cette section."

**Utilisation:**
```php
Route::middleware('admin')->group(function () {
    // Routes protégées aux admins
});
```

---

## Modèle User

**Emplacement:** `app/Models/User.php`

### Fillable
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'pro_type',
    'company_name',
    'tax_id',
    'status',
];
```

### Helper Methods

#### `isAdmin(): bool`
Vérifie si l'utilisateur est un administrateur.
```php
if ($user->isAdmin()) { /* ... */ }
```

#### `isPro(): bool`
Vérifie si l'utilisateur est un professionnel.
```php
if ($user->isPro()) { /* ... */ }
```

#### `isConsumer(): bool`
Vérifie si l'utilisateur est un consommateur.
```php
if ($user->isConsumer()) { /* ... */ }
```

#### `isApproved(): bool`
Vérifie si le compte professionnel est approuvé.
```php
if ($user->isApproved()) { /* ... */ }
```

#### `isPending(): bool`
Vérifie si le compte professionnel est en attente d'approbation.
```php
if ($user->isPending()) { /* ... */ }
```

#### `isActive(): bool`
Vérifie si le compte est actif.
```php
if ($user->isActive()) { /* ... */ }
```

#### `getProTypeLabel(): string`
Retourne le label français du type professionnel.
```php
echo $user->getProTypeLabel();
// Retourne: "Producteur / Agriculteur"
//          "Transformateur / Usine"
//          "Distributeur / Logistique"
//          "Non défini"
```

---

## Workflows

### Workflow 1: Inscription Consommateur

1. **Accès à `/register`**
   - Affichage du formulaire avec choix "Consommateur" sélectionné

2. **Remplissage du formulaire**
   - Name (obligatoire)
   - Email (obligatoire, unique)
   - Password (8+ caractères)
   - Confirmation password
   - Accept terms (obligatoire)

3. **Soumission**
   - Validation serveur
   - Création User avec `role='consumer'`, `status='active'`
   - Authentification automatique
   - Redirection `/` (Home)
   - User est connecté immédiatement

### Workflow 2: Inscription Professionnel

1. **Accès à `/register`**
   - Affichage du formulaire avec choix de type

2. **Sélection "Professionnel"**
   - Affichage dynamique des champs Pro
   - pro_type (sélection: producteur/transformateur/distributeur)
   - company_name (nom ferme/entreprise)
   - tax_id (identifiant fiscal unique)

3. **Soumission**
   - Validation serveur (tous les champs requis)
   - Création User avec `role='pro'`, `status='pending'`
   - Redirection `/login`
   - Message: "Votre compte professionnel a été créé. En attente de validation..."
   - Email notifié

4. **Approbation par Admin** (à implémenter)
   - Admin accède au back-office
   - Valide/Rejette l'inscription Pro
   - Utilisateur reçoit un email de confirmation

5. **Connexion (Après approbation)**
   - User se connecte avec email/password
   - Si `status='pending'` → Erreur "En cours de vérification"
   - Si `status='approved'` → Authentification OK

### Workflow 3: Connexion Admin

1. **Accès à `/login`**
   - Formulaire de connexion

2. **Entrée des identifiants**
   - Email: admin@farmtofork.tn
   - Password: (mot de passe)

3. **Vérification**
   - Authentification
   - Redirection automatique `/admin/dashboard`
   - Accès à toutes les routes backend

### Workflow 4: Déconnexion

1. **Click sur "Déconnexion"**
   - POST `/logout`
   - Destruction de la session
   - Invalidation du token CSRF
   - Redirection `/` (Home)

---

## Commandes Artisan

### Installation de Breeze
```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

### Création des Controllers
```bash
php artisan make:controller Auth/RegisteredUserController
php artisan make:controller Auth/AuthenticatedSessionController
```

### Création du Middleware
```bash
php artisan make:middleware AdminMiddleware
```

### Création de la Migration
```bash
php artisan make:migration add_role_fields_to_users_table
```

### Exécution des Migrations
```bash
php artisan migrate
```

### Rollback de la Migration
```bash
php artisan migrate:rollback
```

### Création d'un User Admin (via tinker)
```bash
php artisan tinker

>>> App\Models\User::create([
    'name' => 'Admin Farm to Fork',
    'email' => 'admin@farmtofork.tn',
    'password' => Hash::make('password'),
    'role' => 'admin',
    'status' => 'active',
]);
```

---

## Configuration

### Locale (Français)
**Fichier:** `config/app.php`

```php
'locale' => env('APP_LOCALE', 'fr'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),
'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),
```

### Environment (.env)
```env
APP_NAME=FarmToFork
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:...
APP_URL=http://localhost:8000
APP_LOCALE=fr

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

---

## Tests

### Tester l'inscription Consommateur
1. Accéder à `http://localhost:8000/register`
2. Sélectionner "Consommateur"
3. Remplir le formulaire
4. Valider → Redirection vers `/`
5. User authentifié

### Tester l'inscription Professionnel
1. Accéder à `http://localhost:8000/register`
2. Sélectionner "Professionnel"
3. Remplir tous les champs (including pro_type, company_name, tax_id)
4. Valider → Redirection vers `/login`
5. Message d'attente affiché
6. User NON authentifié jusqu'à approbation

### Tester la connexion
1. Accéder à `http://localhost:8000/login`
2. Entrer email + password
3. Valider → Redirection basée sur rôle
   - Admin → `/admin/dashboard`
   - Autre → `/`

### Tester l'erreur compte en attente
1. Créer un compte Pro
2. Tentative de connexion avant approbation
3. Message d'erreur: "Votre compte est en cours de vérification..."

---

## TODO: Fonctionnalités à implémenter

- [ ] Dashboard Admin pour approver/rejeter les comptes Pro
- [ ] Email notifications (new registration, approval, rejection)
- [ ] Password reset / Forgot password
- [ ] Email verification
- [ ] Two-factor authentication (2FA)
- [ ] Social login (Google, GitHub, etc.)
- [ ] User profile edit page
- [ ] Account deletion
- [ ] Rate limiting on login attempts
- [ ] Admin audit log

---

## 🔗 Fichiers concernés

- ✅ `app/Models/User.php` - Modèle User avec helpers
- ✅ `app/Http/Controllers/Auth/RegisteredUserController.php` - Inscription
- ✅ `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - Connexion
- ✅ `app/Http/Middleware/AdminMiddleware.php` - Protection des routes admin
- ✅ `database/migrations/2026_10_16_205939_add_role_fields_to_users_table.php` - Migration
- ✅ `resources/views/auth/login.blade.php` - Vue login
- ✅ `resources/views/auth/register.blade.php` - Vue register
- ✅ `routes/web.php` - Routes (auth, frontend, backend)
- ✅ `config/app.php` - Locale FR
- ✅ `bootstrap/app.php` - Middleware registration

---

## Support & Questions

Pour toute question concernant l'authentification, consultez la documentation Laravel officielle:
- [Laravel Authentication](https://laravel.com/docs/12.x/authentication)
- [Laravel Breeze](https://laravel.com/docs/12.x/starter-kits#breeze)
- [Middleware](https://laravel.com/docs/12.x/middleware)
