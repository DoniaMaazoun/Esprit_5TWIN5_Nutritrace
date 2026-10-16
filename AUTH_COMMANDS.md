# 🎯 Commandes d'Authentification Farm to Fork

## 📦 Installation & Configuration

### 1. Installer Laravel Breeze
```bash
composer require laravel/breeze --dev
```

### 2. Exécuter les migrations
```bash
php artisan migrate
```

### 3. Créer un utilisateur Admin (Tinker)
```bash
php artisan tinker

>>> use App\Models\User;
>>> use Illuminate\Support\Facades\Hash;
>>> User::create([
    'name' => 'Admin Farm to Fork',
    'email' => 'admin@farmtofork.tn',
    'password' => Hash::make('password123'),
    'role' => 'admin',
    'status' => 'active',
]);
```

---

## 🌐 URLs à tester

### Routes d'Authentification
- **Inscription:** `http://localhost:8000/register`
- **Connexion:** `http://localhost:8000/login`
- **Déconnexion:** Bouton dans le menu (POST `/logout`)

### Frontend (Public)
- **Accueil:** `http://localhost:8000/`
- **À propos:** `http://localhost:8000/about`
- **Produits:** `http://localhost:8000/products`
- **Contact:** `http://localhost:8000/contact`

### Backend (Protégé - Admin uniquement)
- **Dashboard:** `http://localhost:8000/admin/dashboard`
- **Produits:** `http://localhost:8000/admin/products`
- **Catégories:** `http://localhost:8000/admin/categories`
- **Fermes:** `http://localhost:8000/admin/farms`
- **Traçabilité (Lots):** `http://localhost:8000/admin/batches`
- **Historique:** `http://localhost:8000/admin/history`
- **Certifications:** `http://localhost:8000/admin/certifications`
- **Labels:** `http://localhost:8000/admin/labels`
- **Anti-Greenwashing:** `http://localhost:8000/admin/greenwashing`
- **Éco-Score:** `http://localhost:8000/admin/ecoscore`

---

## 🧪 Cas de test

### Test 1: Inscription Consommateur
```
1. Aller à /register
2. Sélectionner "Consommateur"
3. Remplir:
   - Nom: "Jean Dupont"
   - Email: "jean@example.com"
   - Password: "SecurePass123"
   - Confirm: "SecurePass123"
   - ✓ Accept terms
4. Submit
5. ✅ Redirection automatique vers home (connecté)
```

### Test 2: Inscription Professionnel
```
1. Aller à /register
2. Sélectionner "Professionnel"
3. Champs apparaissent dynamiquement
4. Remplir:
   - Nom: "Michel Agriculteur"
   - Email: "michel@ferme.tn"
   - Password: "FarmerPass123"
   - Type: "Producteur / Agriculteur"
   - Company: "Ferme du Soleil"
   - Tax ID: "12345678901"
   - ✓ Accept terms
5. Submit
6. ✅ Redirection vers login
7. ❌ Tentative de connexion → Erreur "En attente de vérification"
```

### Test 3: Connexion Admin
```
1. Aller à /login
2. Email: "admin@farmtofork.tn"
3. Password: "password123"
4. ✓ Se souvenir de moi
5. Submit
6. ✅ Redirection vers /admin/dashboard
7. ✅ Accès à toutes les routes backend
```

### Test 4: Déconnexion
```
1. Être connecté (consumer ou admin)
2. Trouver le bouton "Déconnexion" dans le menu
3. Click
4. ✅ Redirection vers home
5. ✅ Session détruite
```

### Test 5: Erreur - Identifiants invalides
```
1. Aller à /login
2. Email: "invalide@example.com"
3. Password: "wrongpassword"
4. Submit
5. ❌ Erreur: "Ces identifiants ne correspondent pas à nos enregistrements"
```

---

## 🔑 Authentifiants de test prédéfinis

### Admin
```
Email: admin@farmtofork.tn
Password: password123
Role: admin
Status: active
Accès: /admin/dashboard
```

### Consumer (Pour créer via formulaire)
```
Nom: Test Consumer
Email: consumer@test.tn
Password: password123
Role: consumer
Status: active
Accès: Frontend uniquement
```

### Professional (Pour créer via formulaire)
```
Nom: Test Pro
Email: pro@test.tn
Password: password123
Role: pro
Pro Type: producer
Company: Test Farm
Tax ID: 99999999999
Status: pending (en attente d'approbation)
Accès: Aucun (en attente)
```

---

## 📊 Système des Rôles & Statuts

### Rôles (role)
| Role | Signification | Accès |
|------|---------------|-------|
| `consumer` | Consommateur / Grand public | Frontend |
| `pro` | Professionnel (Prod/Transf/Distrib) | Frontend (après approbation) |
| `admin` | Administrateur | Frontend + Backend |

### Statuts (status)
| Status | Signification | Peut se connecter |
|--------|---------------|-----------------|
| `active` | Compte actif | ✅ OUI |
| `pending` | En attente d'approbation | ❌ NON (error) |
| `approved` | Compte approuvé (Pro) | ✅ OUI |
| `rejected` | Compte rejeté | ❌ NON (error) |

### Types Professionnels (pro_type)
| Type | Signification |
|------|---------------|
| `producer` | Producteur / Agriculteur |
| `processor` | Transformateur / Usine |
| `distributor` | Distributeur / Logistique |

---

## 🔐 Helper Methods sur le User

```php
$user = auth()->user();

// Vérifier le rôle
$user->isAdmin()      // true si role === 'admin'
$user->isPro()        // true si role === 'pro'
$user->isConsumer()   // true si role === 'consumer'

// Vérifier le statut (Pro)
$user->isApproved()   // true si status === 'approved'
$user->isPending()    // true si status === 'pending'
$user->isActive()     // true si status === 'active'

// Get label français
$user->getProTypeLabel() // "Producteur / Agriculteur" etc.
```

**Utilisation dans les views:**
```blade
@if(auth()->user()->isAdmin())
    <a href="/admin">Admin Panel</a>
@endif

@if(auth()->user()->isPro() && auth()->user()->isApproved())
    <p>Account approuvé</p>
@endif
```

---

## 📝 Fichiers de configuration

### `config/app.php`
```php
'locale' => env('APP_LOCALE', 'fr'),  // ← Français
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fr'),
'faker_locale' => env('APP_FAKER_LOCALE', 'fr_FR'),
```

### `.env` (exemple)
```env
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

---

## 🚀 Déploiement (Production)

### Avant de passer à la production:

1. **Vérifier les erreurs:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Compiler les assets:**
   ```bash
   npm run build
   ```

3. **Permissions:**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

4. **Database:**
   ```bash
   php artisan migrate --force
   ```

5. **Redis/Cache:**
   ```bash
   redis-cli flushall
   ```

---

## 🐛 Troubleshooting

### Error: "Middleware not found"
**Solution:** Vérifier `bootstrap/app.php`
```php
$middleware->alias(['admin' => \App\Http\Middleware\AdminMiddleware::class]);
```

### Error: "Column not found"
**Solution:** Exécuter la migration
```bash
php artisan migrate
```

### Erreur: "Authentification failed"
**Solution:** Vérifier le password avec:
```bash
php artisan tinker
>>> Hash::make('password123')
```

### Dynamic form fields ne s'affichent pas
**Solution:** Vérifier la console du navigateur (F12)
- Voir si le JavaScript est exécuté
- Vérifier la classe CSS `.pro-fields`

---

## 📚 Documentation complète

**Pour plus de détails, voir:**
- `AUTHENTICATION.md` - Guide complet (50+ pages)
- `AUTH_SETUP_SUMMARY.md` - Résumé des étapes
- Code des controllers: `app/Http/Controllers/Auth/`
- Code des vues: `resources/views/auth/`

---

## ✅ Checklist Final

- [x] Breeze installé
- [x] Migration exécutée
- [x] User model mis à jour
- [x] Controllers créés
- [x] Middleware créé
- [x] Routes configurées
- [x] Vues en français
- [x] Validation complète
- [x] Helper methods implémentées
- [x] Documentation complète
- [x] Commit effectué sur la branche `feature/authentication`

---

## 🎯 Prochaines étapes

1. ✅ Authentification de base - **COMPLÈTE**
2. ⏳ Dashboard Admin pour valider comptes Pro
3. ⏳ Notifications par email
4. ⏳ Password reset / Forgot password
5. ⏳ Two-factor authentication
6. ⏳ Social login (Google, GitHub)

---

**Branche:** `feature/authentication`
**Commit:** `a83b832`
**Status:** ✅ PRÊTE POUR MERGE VERS MAIN
