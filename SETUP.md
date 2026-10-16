# 🚀 Installation Rapide - Farm to Fork

## ⚠️ Prérequis

Avant de commencer, assure-toi d'avoir:
- ✅ **XAMPP** ou **WAMP** installé (avec MySQL)
- ✅ **PHP 8.2+** 
- ✅ **Composer** installé
- ✅ **Git** installé (optionnel)

---

## 🎯 Installation en 1 Minute

### Option 1: PowerShell (Windows - Recommandé)

```powershell
# 1. Ouvre PowerShell en tant qu'administrateur
# 2. Va dans le dossier du projet
cd C:\Users\donia\Desktop\FarmToFork

# 3. Lance le script de setup
powershell -ExecutionPolicy Bypass -File setup.ps1
```

### Option 2: CMD (Windows - Alternatif)

```cmd
# 1. Ouvre CMD
# 2. Va dans le dossier du projet
cd C:\Users\donia\Desktop\FarmToFork

# 3. Lance le script
setup.bat
```

### Option 3: Manuel (Étapes)

```bash
# 1. Créer la base de données
mysql -u root -e "CREATE DATABASE IF NOT EXISTS farm_to_fork CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Installer les dépendances
composer install

# 3. Générer la clé
php artisan key:generate --force

# 4. Exécuter les migrations
php artisan migrate --force

# 5. Remplir la base avec les données de test
php artisan db:seed --force
```

---

## ✅ Vérification

Une fois l'installation terminée:

```bash
# Lancer le serveur
php artisan serve
```

Ouvre: `http://localhost:8000`

Tu devrais voir la page d'accueil Farm to Fork! 🎉

---

## 👤 Utilisateurs de Test Créés

| Email | Password | Rôle | Accès |
|-------|----------|------|-------|
| `admin@test.tn` | password123 | Admin | Backend `/admin/dashboard` |
| `consumer@test.tn` | password123 | Consumer | Frontend |
| `pro@test.tn` | password123 | Pro | Bloqué (pending) |
| `pro-approved@test.tn` | password123 | Pro | Frontend (approved) |

---

## 🔧 Troubleshooting

### Erreur: "MySQL n'est pas disponible"
- Ouvre **XAMPP Control Panel**
- Clique **Start** pour **MySQL**
- Attends que le statut devienne vert

### Erreur: "Composer not found"
- Télécharge Composer: https://getcomposer.org/download/
- Relance le script

### Erreur: "Connection refused"
- MySQL ne tourne pas
- Vérifies le `.env` (DB_HOST, DB_PORT, DB_USERNAME)

---

## 📚 Documentation

- `STRUCTURE.md` - Architecture du projet
- `AUTHENTICATION.md` - Système d'authentification
- `USER_APPROVALS.md` - Gestion des approbations
- `EMAIL_NOTIFICATIONS.md` - Notifications par email
- `MYSQL_SETUP.md` - Configuration MySQL détaillée
- `QUICK_START.md` - Guide de démarrage

---

## 🎓 Prochaines Étapes

Après l'installation:

1. **Se connecter:** `http://localhost:8000/login`
2. **Admin:** Accédez à `/admin/users/approvals` pour approuver les comptes
3. **Frontend:** Explorez la page d'accueil
4. **Backend:** Consultez le dashboard admin

---

**Status:** ✅ Prêt pour le développement
