# 📧 Système de Notifications par Email

## 🎯 Vue d'ensemble

Farm to Fork envoie des emails automatiques pour notifier les utilisateurs lors de:
- ✅ **Approbation de compte professionnel**
- ❌ **Rejet de compte professionnel**

---

## 📨 Emails Implémentés

### 1️⃣ Email d'Approbation - ProAccountApproved

**Classe:** `App\Mail\ProAccountApproved`
**Déclencheur:** Quand un admin clique sur "Approuver"
**Destinataire:** L'email du compte professionnel

#### 📋 Contenu:
- ✅ Titre: "Compte Approuvé!"
- 📝 Salutation personnalisée avec le nom
- ✨ Message de félicitations
- 📖 Informations du compte (Entreprise, Type d'activité)
- 🔗 Lien de connexion
- 📚 Guide "Comment se connecter"
- 💡 Informations sur les fonctionnalités disponibles

#### 📧 Exemple de sujet:
```
✅ Votre compte professionnel Farm to Fork a été approuvé!
```

#### 🎨 Design:
- Gradient vert (#1e3a2b → #2d5a27)
- Icône de succès ✅
- Boîte verte de confirmation
- Bouton d'action vert

---

### 2️⃣ Email de Rejet - ProAccountRejected

**Classe:** `App\Mail\ProAccountRejected`
**Déclencheur:** Quand un admin clique sur "Rejeter"
**Destinataire:** L'email du compte professionnel

#### 📋 Contenu:
- ⚠️ Titre: "Demande Examinée"
- 📝 Salutation personnalisée avec le nom
- 🚫 Message d'explication
- 🔍 Raison du rejet (si fournie)
- 📋 Prochaines étapes
- 📞 Contact support
- 🔄 Option pour réessayer

#### 📧 Exemple de sujet:
```
❌ Votre demande d'inscription a été examinée
```

#### 🎨 Design:
- Gradient rouge (#dc3545 → #c82333)
- Icône d'alerte ⚠️
- Boîte rouge pour la raison
- Boîte bleue pour les prochaines étapes
- Lien de support

---

## 🔧 Configuration des Emails

### Variables d'Environnement (.env)

```env
# Configuration SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@farmtofork.tn
MAIL_FROM_NAME="Farm to Fork"
```

### Fournisseurs Email Recommandés:
- **Mailtrap** - Pour le développement (gratuit)
- **Gmail** - Avec mot de passe d'application
- **SendGrid** - Pour la production (scalable)
- **AWS SES** - Haute volume
- **Mailgun** - API-first

---

## 📝 Templates Email

### Emplacement:
```
resources/views/mail/
├── pro-account-approved.blade.php
└── pro-account-rejected.blade.php
```

### Structure:
```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Styles CSS -->
</head>
<body>
    <div class="container">
        <div class="header"><!-- Logo et titre --></div>
        <div class="content"><!-- Contenu principal --></div>
        <div class="footer"><!-- Pied de page --></div>
    </div>
</body>
</html>
```

### Personnalisation:
Chaque template utilise des variables Blade:
```blade
{{ $user->name }}           <!-- Nom de l'utilisateur -->
{{ $user->email }}          <!-- Email -->
{{ $user->company_name }}   <!-- Nom de l'entreprise -->
{{ $user->getProTypeLabel() }} <!-- Type d'activité -->
{{ $reason }}               <!-- Raison du rejet (optionnel) -->
{{ $loginUrl }}             <!-- URL de connexion -->
{{ $registerUrl }}          <!-- URL d'inscription -->
```

---

## 🚀 Utilisation

### Envoyer un Email d'Approbation

**Dans le Controller:**
```php
use App\Mail\ProAccountApproved;
use Illuminate\Support\Facades\Mail;

$user = User::find(1);
Mail::to($user->email)->send(new ProAccountApproved($user));
```

**Cela déclenche automatiquement dans:**
`App\Http\Controllers\Admin\UserApprovalController@approve`

### Envoyer un Email de Rejet

**Dans le Controller:**
```php
use App\Mail\ProAccountRejected;
use Illuminate\Support\Facades\Mail;

$user = User::find(1);
$reason = "Les documents fournis sont incomplets.";
Mail::to($user->email)->send(new ProAccountRejected($user, $reason));
```

**Cela déclenche automatiquement dans:**
`App\Http\Controllers\Admin\UserApprovalController@reject`

---

## ⚡ Gestion des Erreurs

### Try-Catch Pattern:
```php
try {
    Mail::to($user->email)->send(new ProAccountApproved($user));
} catch (\Exception $e) {
    \Log::error('Erreur email: ' . $e->getMessage());
    // L'utilisateur est quand même approuvé
    // Mais on enregistre l'erreur
}
```

### Logs:
Les erreurs d'email sont enregistrées dans:
```
storage/logs/laravel.log
```

---

## 🧪 Test des Emails

### 1️⃣ Configuration SMTP de Test (Mailtrap)

1. Créer un compte sur [Mailtrap.io](https://mailtrap.io)
2. Copier les credentials SMTP
3. Mettre à jour `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxxxx
MAIL_PASSWORD=xxxxx
MAIL_ENCRYPTION=tls
```

### 2️⃣ Test en Ligne de Commande

```bash
php artisan tinker

>>> use App\Models\User;
>>> use App\Mail\ProAccountApproved;
>>> use Illuminate\Support\Facades\Mail;

>>> $user = User::find(1);
>>> Mail::to($user->email)->send(new ProAccountApproved($user));
```

### 3️⃣ Test via la UI

1. Se connecter en tant qu'Admin
2. Aller à `/admin/users/approvals`
3. Cliquer sur "Approuver"
4. Vérifier Mailtrap pour l'email reçu

---

## 📊 Statuts d'Email

### Tracking:
Actuellement, les emails ne sont PAS tracés (pas d'enregistrement en base).

#### À implémenter:
```php
// Créer une table email_logs
Schema::create('email_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id');
    $table->string('type'); // 'approved', 'rejected'
    $table->string('email');
    $table->string('status'); // 'sent', 'failed', 'bounced'
    $table->text('error')->nullable();
    $table->timestamps();
});
```

---

## 🔔 Email de Notification pour les Admins (TODO)

### À implémenter:
- Email quand un nouveau compte Pro s'inscrit
- Email quotidien avec les comptes en attente
- Rappel pour les comptes en attente depuis X jours

---

## 🌍 Multilangues (TODO)

### Actuellement:
Tous les emails sont en **Français**.

### À implémenter:
```php
// Détecter la langue de l'utilisateur
$language = $user->language ?? 'fr';

// Envoyer dans la bonne langue
Mail::to($user->email)
    ->locale($language)
    ->send(new ProAccountApproved($user));
```

---

## 📱 Responsive Design

### Tous les templates sont optimisés pour:
- ✅ Desktop
- ✅ Tablette
- ✅ Mobile

Testez avec:
- Gmail
- Outlook
- Apple Mail
- Thunderbird

---

## 🔐 Sécurité & Conformité

### RGPD:
- ✅ Les emails contiennent un lien de désinscription (TODO)
- ✅ Pas de données sensibles en clair
- ✅ Lien d'annuler la souscription (TODO)

### SPF/DKIM/DMARC:
À configurer pour la production.

---

## 🐛 Troubleshooting

### "Les emails ne s'envoient pas"

1. **Vérifier la configuration:**
   ```bash
   php artisan tinker
   >>> config('mail.mailer')
   >>> config('mail.from.address')
   ```

2. **Vérifier les logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Tester SMTP:**
   ```bash
   php artisan mail:send-test your@email.com
   ```

### "Email arrive en spam"

- Configurer SPF/DKIM
- Ajouter un logo/branding
- Éviter les mots-clés spam
- Tester avec [Mail-tester.com](https://www.mail-tester.com)

---

## 📚 Resources

- [Laravel Mail Documentation](https://laravel.com/docs/12.x/mail)
- [Mailable Classes](https://laravel.com/docs/12.x/mail#generating-mailables)
- [Email Templates](https://laravel.com/docs/12.x/mail#markdown-mailables)
- [MJML Framework](https://mjml.io) - Alternative pour HTML emails

---

## ✅ Checklist

- [x] Mail ProAccountApproved créé
- [x] Mail ProAccountRejected créé
- [x] Template HTML pro-account-approved
- [x] Template HTML pro-account-rejected
- [x] Envoi d'email dans le controller approve()
- [x] Envoi d'email dans le controller reject()
- [x] Gestion des erreurs
- [ ] Email tracking en base de données
- [ ] Email pour les admins (nouveaux comptes)
- [ ] Multilangues
- [ ] SPF/DKIM/DMARC
- [ ] Unsubscribe link
- [ ] Email scheduling (queue)

---

**Version:** 1.0
**Date:** 2026-10-16
**Status:** ✅ IMPLÉMENTÉ (approbation & rejet)
