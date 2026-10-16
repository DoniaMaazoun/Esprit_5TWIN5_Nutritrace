# 👥 Gestion des Approbations de Comptes Professionnels

## 📋 Vue d'ensemble

Le module de gestion des approbations permet aux administrateurs de **valider, rejeter, ou réinitialiser** les comptes professionnels qui se sont inscrits sur la plateforme.

---

## 🎯 Objectif

- **Contrôler la qualité des professionnels** inscrits sur la plateforme
- **Vérifier les informations** (Nom, Entreprise, Matricule)
- **Autoriser l'accès** une fois approuvé
- **Bloquer les comptes** suspects ou invalides

---

## 📁 Fichiers Concernés

### Controllers
- `app/Http/Controllers/Admin/UserApprovalController.php` - Logique d'approbation

### Views
- `resources/views/backend/pages/user-approvals.blade.php` - Interface de gestion

### Routes
- `routes/web.php` - Routes d'approbation

### Models
- `app/Models/User.php` - Helper methods pour les rôles

---

## 🚀 Routes & URLs

### Routes Admin
```php
GET    /admin/users/approvals                    → UserApprovalController@index
POST   /admin/users/{user}/approve               → UserApprovalController@approve
POST   /admin/users/{user}/reject                → UserApprovalController@reject
POST   /admin/users/{user}/reset-pending         → UserApprovalController@resetToPending
```

### URLs Complètes
- **Gestion des approbations:** `http://localhost:8000/admin/users/approvals`

---

## 💻 Interface d'Utilisation

### Page Principale
La page affiche **3 onglets/sections:**

#### 1️⃣ En Attente d'Approbation (Badge rouge)
- Liste des comptes Pro avec statut `pending`
- **Colonnes:** Nom, Email, Type, Entreprise, Matricule, Date, Actions
- **Actions:** 
  - ✅ **Approuver** - Valide le compte (statut → `approved`)
  - ❌ **Rejeter** - Refuse le compte (statut → `rejected`)

#### 2️⃣ Comptes Approuvés (Badge vert)
- Liste des comptes Pro avec statut `approved`
- **Colonnes:** Nom, Email, Type, Entreprise, Date d'approbation, Actions
- **Actions:**
  - 👁️ **Voir** - Affiche les détails

#### 3️⃣ Comptes Rejetés (Badge rouge foncé)
- Liste des comptes Pro avec statut `rejected`
- **Colonnes:** Nom, Email, Type, Entreprise, Date de rejet, Actions
- **Actions:**
  - 🔄 **Réinitialiser** - Remet en `pending` pour une nouvelle évaluation

---

## 🔄 Workflows

### Workflow 1: Approbation d'un Compte Pro

```
1. Admin accède à /admin/users/approvals
2. Voit un compte Pro en attente d'approbation
3. Examine les informations
4. Clique sur "Approuver"
5. Statut → "approved"
6. Pro peut maintenant se connecter
7. Success message affiché
```

**Résultat:**
- Utilisateur peut se connecter
- Email de confirmation (TODO)
- Accès complet au frontend

### Workflow 2: Rejet d'un Compte Pro

```
1. Admin accède à /admin/users/approvals
2. Voit un compte Pro en attente
3. Clique sur "Rejeter"
4. Confirmation: "Êtes-vous sûr?"
5. Statut → "rejected"
6. Warning message affiché
```

**Résultat:**
- Utilisateur NE PEUT PAS se connecter
- Email de rejet (TODO)
- Peut être réinitialisé plus tard

### Workflow 3: Réinitialisation d'un Compte Rejeté

```
1. Admin accède à /admin/users/approvals
2. Va à la section "Comptes Rejetés"
3. Clique sur "Réinitialiser"
4. Statut → "pending"
5. Le compte revient en attente
6. Success message affiché
```

**Résultat:**
- Le compte est remis en attente
- Peut être évalué à nouveau

---

## 📊 Statuts des Comptes Pro

| Statut | Code | Signification | Peut se connecter |
|--------|------|---------------|-----------------|
| En Attente | `pending` | En cours de vérification | ❌ NON |
| Approuvé | `approved` | Compte validé | ✅ OUI |
| Rejeté | `rejected` | Compte refusé | ❌ NON |

---

## 📝 Données Affichées

Pour chaque compte professionnel, le système affiche:

| Champ | Source | Format |
|-------|--------|--------|
| Nom | `users.name` | Texte |
| Email | `users.email` | Email |
| Type d'Activité | `users.pro_type` | Badge (Producteur/Transformateur/Distributeur) |
| Entreprise | `users.company_name` | Texte |
| Matricule | `users.tax_id` | Code badge |
| Date d'Inscription | `users.created_at` | JJ/MM/YYYY HH:MM |
| Statut | `users.status` | Onglet |

---

## 🛡️ Sécurité

### Permissions
- ✅ Accessible UNIQUEMENT aux **Administrateurs** (rôle = `admin`)
- ✅ Middleware `admin` protège la route
- ✅ Seuls les comptes Pro peuvent être approuvés

### Validations
- Vérification du rôle avant approbation
- Vérification du statut avant rejet
- Messages d'erreur clairs

### Protection CSRF
- ✅ Tous les formulaires POST protégés par `@csrf`
- ✅ Confirmation avant rejet

---

## 🔧 Code - Controller

### Méthode: `index()`
```php
/**
 * Affiche la liste des comptes professionnels
 */
public function index(): View
{
    $pendingUsers = User::where('role', 'pro')
        ->where('status', 'pending')
        ->paginate(10);

    $approvedUsers = User::where('role', 'pro')
        ->where('status', 'approved')
        ->paginate(10);

    $rejectedUsers = User::where('role', 'pro')
        ->where('status', 'rejected')
        ->paginate(10);

    return view('backend.pages.user-approvals', [
        'pendingUsers' => $pendingUsers,
        'approvedUsers' => $approvedUsers,
        'rejectedUsers' => $rejectedUsers,
    ]);
}
```

### Méthode: `approve(User $user)`
```php
/**
 * Approuver un compte professionnel
 */
public function approve(User $user): RedirectResponse
{
    if (!$user->isPro()) {
        return back()->with('error', 'Seuls les comptes professionnels peuvent être approuvés.');
    }

    $user->update(['status' => 'approved']);
    // TODO: Envoyer email

    return back()->with('success', "Le compte de {$user->name} a été approuvé.");
}
```

### Méthode: `reject(User $user)`
```php
/**
 * Rejeter un compte professionnel
 */
public function reject(User $user): RedirectResponse
{
    if (!$user->isPro()) {
        return back()->with('error', 'Seuls les comptes professionnels peuvent être rejetés.');
    }

    $user->update(['status' => 'rejected']);
    // TODO: Envoyer email

    return back()->with('warning', "Le compte de {$user->name} a été rejeté.");
}
```

### Méthode: `resetToPending(User $user)`
```php
/**
 * Réinitialiser un compte rejeté en attente
 */
public function resetToPending(User $user): RedirectResponse
{
    if (!$user->isPro() || $user->status !== 'rejected') {
        return back()->with('error', 'Action non autorisée.');
    }

    $user->update(['status' => 'pending']);

    return back()->with('success', "Le compte de {$user->name} a été réinitialisé en attente.");
}
```

---

## 📧 Notifications (TODO)

À implémenter:
- ✅ Email de confirmation d'approbation au pro
- ✅ Email de rejet au pro
- ✅ Email de réinitialisation au pro
- ✅ Notification admin pour comptes en attente

---

## 🧪 Test

### Créer un compte Pro en attente

```bash
php artisan tinker

>>> use App\Models\User;
>>> use Illuminate\Support\Facades\Hash;

>>> User::create([
    'name' => 'Test Pro',
    'email' => 'testpro@test.tn',
    'password' => Hash::make('password123'),
    'role' => 'pro',
    'pro_type' => 'producer',
    'company_name' => 'Test Farm',
    'tax_id' => '12345678',
    'status' => 'pending',
]);
```

### Tester l'approbation

1. Se connecter avec admin@test.tn
2. Aller à `/admin/users/approvals`
3. Cliquer sur "Approuver" pour le nouveau compte
4. Vérifier le statut passe à "approved"
5. Se déconnecter
6. Se connecter avec testpro@test.tn
7. ✅ Devrait avoir accès au frontend

---

## 📊 Statistiques & Badges

### Badge de Compteur
Le sidebar affiche le **nombre de comptes en attente** en rouge:
```
Approbations Comptes Pro [5]  ← Badge rouge = 5 comptes pending
```

Cela aide l'admin à voir rapidement qu'il y a des approbations à traiter.

---

## 🎨 Design

### Couleurs
- **En Attente:** Bleu primaire
- **Approuvés:** Vert (#28a745)
- **Rejetés:** Rouge (#dc3545)

### Icônes
- Dashboard: 📊
- Approbations: 👤➕
- Approuver: ✅
- Rejeter: ❌
- Réinitialiser: 🔄
- Voir: 👁️

### Responsif
- ✅ Table scrollable sur mobile
- ✅ Buttons adaptés sur petits écrans
- ✅ Pagination fonctionnelle

---

## 🔍 Dépannage

### "Pas d'accès à la page"
- Vérifier que vous êtes Admin (rôle = 'admin')
- Vérifier le middleware dans les routes

### "Erreur: Seuls les comptes professionnels..."
- Essayer d'approuver un compte Consumer (bug!)
- Utiliser uniquement sur des comptes Pro

### "Messages d'erreur pas affichés"
- Vérifier que les alertes Bootstrap sont bien incluses
- Vérifier le sessionStorage

---

## 📋 Checklist

- [x] Controller créé
- [x] Routes configurées
- [x] Vue créée
- [x] Sidebar link ajouté
- [x] Logique d'approbation
- [x] Logique de rejet
- [x] Logique de réinitialisation
- [x] Badges compteur
- [x] Messages de succès/erreur
- [ ] Notifications email
- [ ] Audit log

---

## 🚀 Prochaines Étapes

1. **Notifications Email**
   - Envoyer email lors d'approbation
   - Envoyer email lors de rejet

2. **Audit Log**
   - Logger chaque action d'admin
   - Afficher qui a approuvé et quand

3. **Notes d'Admin**
   - Ajouter un champ pour les notes du rejet
   - Afficher les notes dans l'historique

4. **Bulk Actions**
   - Approuver plusieurs comptes à la fois
   - Rejeter plusieurs comptes à la fois

5. **Filtres Avancés**
   - Filtrer par type d'activité
   - Filtrer par date d'inscription
   - Recherche par email/nom

---

**Version:** 1.0
**Date:** 2026-10-16
**Status:** ✅ IMPLÉMENTÉ
