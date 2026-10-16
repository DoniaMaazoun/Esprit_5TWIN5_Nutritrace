# 🗄️ Configuration MySQL - Farm to Fork

## 📋 Configuration Requise

Le projet est maintenant configuré pour utiliser **MySQL** à la place de SQLite.

### Paramètres Actuels (.env)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=farm_to_fork
DB_USERNAME=root
DB_PASSWORD=
```

---

## 📥 Installation de MySQL

### Option 1: XAMPP (Recommandé - Windows)

**Télécharge XAMPP:**
https://www.apachefriends.org/

**Installation:**
1. Télécharge XAMPP
2. Lance l'installateur
3. Sélectionne **Apache** et **MySQL**
4. Installe dans `C:\xampp`

**Démarrage:**
1. Ouvre le **XAMPP Control Panel**
2. Clique sur **Start** pour **Apache** et **MySQL**

**Vérification:**
```
http://localhost/phpmyadmin
```

---

### Option 2: WAMP (Alternative)

**Télécharge WAMP:**
https://www.wampserver.com/

**Installation similaire à XAMPP**

---

### Option 3: MySQL Standalone

**Télécharge MySQL Community Server:**
https://dev.mysql.com/downloads/mysql/

**Installation manuelle (avancé)**

---

## ✅ Créer la Base de Données

Une fois MySQL lancé, crée la base de données `farm_to_fork`:

### Via PhpMyAdmin (Interface Web)

1. **Ouvre PhpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **Clique sur "New" ou "Nouvelle base de données"**

3. **Remplis le formulaire:**
   - Nom: `farm_to_fork`
   - Collation: `utf8mb4_unicode_ci`

4. **Clique sur "Create"**

### Via CLI MySQL

```bash
mysql -u root -p

# Entre ton mot de passe (vide par défaut)

CREATE DATABASE farm_to_fork CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

EXIT;
```

---

## 🚀 Exécuter les Migrations

Une fois la base créée, exécute les migrations Laravel:

```bash
php artisan migrate
```

**Output attendu:**
```
Running migrations...

  Illuminate\Foundation\Database\Migrations\
  2014_10_12_000000_create_users_table ............. 94ms DONE
  Illuminate\Foundation\Database\Migrations\
  2014_10_12_100000_create_password_reset_tokens_table . 78ms DONE
  Illuminate\Foundation\Database\Migrations\
  2014_10_12_200000_add_two_factor_columns_to_users_table . 67ms DONE
  Illuminate\Foundation\Database\Migrations\
  2026_10_16_205939_add_role_fields_to_users_table . 123ms DONE

Migrated successfully.
```

---

## 🌱 Remplir la Base (Seeder)

Exécute les seeders pour créer les utilisateurs de test:

```bash
php artisan db:seed
```

**Utilisateurs créés:**
- ✅ admin@test.tn (Admin)
- ✅ consumer@test.tn (Consumer)
- ✅ pro@test.tn (Pro - Pending)
- ✅ pro-approved@test.tn (Pro - Approved)

---

## 🔍 Vérifier dans PhpMyAdmin

1. **Ouvre PhpMyAdmin:** `http://localhost/phpmyadmin`

2. **À gauche, clique sur `farm_to_fork`**

3. **Tu devrais voir les tables:**
   - `users` ✓
   - `password_reset_tokens` ✓
   - `sessions` ✓
   - `jobs` ✓
   - `job_batches` ✓
   - `failed_jobs` ✓
   - `cache` ✓
   - `cache_locks` ✓

4. **Clique sur `users` et tu verras les 4 comptes de test**

---

## 🧪 Test de Connexion

```bash
php artisan tinker

>>> DB::connection()->getPDO();
# Si aucune erreur → Connexion OK!

>>> User::all();
# Tu devrais voir les 4 users
```

---

## 🔧 Configuration Personnalisée

Si tu as une configuration différente, modifie le `.env`:

```env
DB_HOST=localhost          # ou 127.0.0.1
DB_PORT=3306              # Port MySQL (3306 par défaut)
DB_DATABASE=farm_to_fork  # Nom de la base
DB_USERNAME=root          # Utilisateur MySQL
DB_PASSWORD=              # Mot de passe (vide par défaut pour root)
```

---

## 🚨 Erreurs Courantes

### Erreur 1: "SQLSTATE[HY000] [2002] No such file or directory"
**Cause:** MySQL n'est pas lancé
**Solution:** 
- Démarrer XAMPP/WAMP
- Vérifier que MySQL tourne

### Erreur 2: "SQLSTATE[HY000]: General error: 1030 Got error"
**Cause:** Base de données n'existe pas
**Solution:**
```bash
mysql -u root -p
CREATE DATABASE farm_to_fork CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Erreur 3: "SQLSTATE [28000]: Invalid credentials"
**Cause:** Mauvais username/password
**Solution:** Vérifier le `.env` DB_USERNAME et DB_PASSWORD

---

## 📊 Structure de la Base

### Table `users`
```sql
id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
email_verified_at TIMESTAMP NULL
password        VARCHAR(255)
remember_token  VARCHAR(100) NULL
role            VARCHAR(20) DEFAULT 'consumer'
pro_type        VARCHAR(50) NULL
company_name    VARCHAR(255) NULL
tax_id          VARCHAR(255) UNIQUE NULL
status          VARCHAR(20) DEFAULT 'active'
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Valeurs des Enums
**role:**
- `consumer` - Consommateur
- `pro` - Professionnel
- `admin` - Administrateur

**status:**
- `active` - Actif
- `pending` - En attente
- `approved` - Approuvé
- `rejected` - Rejeté

**pro_type:**
- `producer` - Producteur
- `processor` - Transformateur
- `distributor` - Distributeur

---

## 💾 Backup & Export

### Exporter la Base

**Via PhpMyAdmin:**
1. Clique sur `farm_to_fork`
2. Clique sur "Export"
3. Sélectionne "SQL" ou "CSV"
4. Clique "Go"

**Via CLI:**
```bash
mysqldump -u root -p farm_to_fork > backup.sql
```

### Importer un Backup

```bash
mysql -u root -p farm_to_fork < backup.sql
```

---

## 📝 Commandes Utiles

```bash
# Voir les tables
php artisan tinker
>>> Schema::getTables();

# Voir les colonnes d'une table
>>> Schema::getColumns('users');

# Voir les données
>>> DB::table('users')->get();

# Compter les users
>>> DB::table('users')->count();
```

---

## ✅ Checklist de Setup

- [ ] MySQL installé et lancé
- [ ] Base de données `farm_to_fork` créée
- [ ] `.env` configuré avec DB_CONNECTION=mysql
- [ ] Migrations exécutées (`php artisan migrate`)
- [ ] Seeders exécutés (`php artisan db:seed`)
- [ ] PhpMyAdmin accessible et affiche les données
- [ ] Connexion Laravel testée (`php artisan tinker`)
- [ ] Application lancée (`php artisan serve`)

---

**État:** ✅ Configuré pour MySQL
**Port MySQL:** 3306
**Base de données:** farm_to_fork
**Utilisateur:** root
**Mot de passe:** (vide)
