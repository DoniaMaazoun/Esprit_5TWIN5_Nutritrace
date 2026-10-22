# CI/CD, Docker et déploiement Render — Nutritrace / Farm to Fork

L'application est **monolithique** (un seul Laravel : front, back-office, modules Producteur,
Transformateur, Distributeur). Elle est livrée dans **une seule image Docker** et testée/déployée
par **un seul pipeline** GitHub Actions.

```
 push / pull request
        │
        ▼
 ┌──────────────┐   ┌────────────────────────┐   ┌──────────────────────┐   ┌──────────────────────┐
 │ 1. Qualité   │──▶│ 2. Tests               │──▶│ 3. Image Docker      │──▶│ 4. Déploiement Render│
 │ composer,    │   │ unitaires + fonction-  │   │ build + test de      │   │ (branche main)       │
 │ syntaxe PHP, │   │ nels, SQLite ET MySQL  │   │ fumée app + MySQL,   │   │ Deploy Hook + suivi  │
 │ Pint, audit  │   │ + couverture de code   │   │ publication GHCR     │   │ + vérif. /up         │
 └──────────────┘   └────────────────────────┘   └──────────────────────┘   └──────────────────────┘
```

| Fichier | Rôle |
|---|---|
| `.github/workflows/ci-cd.yml` | Le pipeline unique (remplace l'ancien `ci.yml`) |
| `Dockerfile` | Image PHP 8.3 + Apache de l'application |
| `docker/entrypoint.sh` | Démarrage : port Render, attente MySQL, migrations, données de démo, caches |
| `docker-compose.yml` | Docker Desktop : MySQL 8 + phpMyAdmin (+ l'application avec le profil `app`) |
| `render.yaml` | Blueprint Render : service web Docker gratuit (base MySQL externe Aiven) |
| `tests/Unit`, `tests/Feature` | Tests unitaires et fonctionnels |

---

## 1. Base de données sur Docker Desktop (développement)

```bash
docker compose up -d
```

- MySQL 8 : `127.0.0.1:3307` — base `farm_to_fork`, utilisateur `nutritrace` / `nutritrace`
  (port 3307 pour ne pas gêner le MySQL de XAMPP sur 3306)
- phpMyAdmin : http://localhost:8081

Dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=farm_to_fork
DB_USERNAME=nutritrace
DB_PASSWORD=nutritrace
```

Puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### Lancer toute l'application en conteneur (comme en production)

```bash
docker compose --profile app up -d --build
```

→ http://localhost:8080 (la variable `APP_KEY` est lue dans `.env`). Les migrations et les données de
démo sont appliquées automatiquement au premier démarrage.

Si le port 8080 est déjà utilisé (Jenkins, Tomcat…), choisir un autre port :

```powershell
$env:APP_PORT=8090; docker compose --profile app up -d
```

---

## 2. Tests

```bash
php artisan test                    # tous les tests
php artisan test --testsuite=Unit   # tests unitaires seulement
php artisan test --filter=ModuleProduit
```

Les tests utilisent SQLite en mémoire (`phpunit.xml`) : ils ne touchent pas à la base de développement.
En CI, ils sont joués **deux fois** : sur SQLite et sur **MySQL 8** (comme en production).

| Fichier | Ce qui est testé |
|---|---|
| `tests/Unit/ProductIntelligenceTest.php` | Fraîcheur / date limite, prix conseillé, score de conformité, prévision |
| `tests/Unit/HarvestAndFarmModelTest.php` | Conversions kg, rendement, cycle de culture, libellés |
| `tests/Feature/ModuleProduit/FarmManagementTest.php` | CRUD exploitations, contrôle de saisie, droits, export CSV |
| `tests/Feature/ModuleProduit/HarvestManagementTest.php` | CRUD produits, règles métier, photo, droits producteur/admin, étiquette |
| `tests/Feature/ModuleProduit/CategoryManagementTest.php` | CRUD catégories, unicité, suppression bloquée si utilisée |
| `tests/Feature/ModuleProduit/CatalogAndTraceabilityTest.php` | Catalogue, recherche intelligente, page d'origine, « Tracer un lot », vitrines, connexion |
| `tests/Feature/ModuleProduit/AiFeaturesTest.php` | IA : moteur local, appel au LLM simulé (`Http::fake`), repli en cas d'erreur |

---

## 3. Déploiement sur Render

### Première mise en place (une seule fois)

Tout est **gratuit** : l'application sur l'offre Free de Render, la base sur **Aiven for MySQL**
(offre Free, vrai MySQL 8 — le module Transformateur utilise `DATE_FORMAT`, propre à MySQL).

1. **Aiven** (https://console.aiven.io) → *Create service* → **MySQL**, offre **Free**. Une fois le
   service démarré, noter dans *Connection information* : Host, Port, Password, et télécharger le
   **CA certificate**. (La base `defaultdb` et l'utilisateur `avnadmin` sont créés par Aiven.)
2. **Render → New → Blueprint** → choisir le dépôt GitHub. Render lit `render.yaml` et crée le
   service web `nutritrace` (Docker, offre gratuite). Renseigner les variables `sync: false` :
   - `DB_HOST`, `DB_PORT`, `DB_PASSWORD` : valeurs Aiven
   - `DB_SSL_CA_PEM` : le contenu complet du fichier `ca.pem` d'Aiven (connexion TLS ; le conteneur
     l'écrit dans un fichier et le passe à PDO via `MYSQL_ATTR_SSL_CA`)
   - `AI_API_KEY` (optionnel)

   `APP_KEY` n'est pas à saisir : Render génère `APP_SECRET` et le conteneur en dérive la clé Laravel.
   `APP_URL` est fixé dans `render.yaml` (`https://nutritrace-1o91.onrender.com`).

   Au premier démarrage, le conteneur applique les migrations puis charge les données de démo.
   Les migrations sont compatibles avec `sql_require_primary_key` (activé par défaut chez Aiven).
3. Service `nutritrace` → **Settings → Deploy Hook** : copier l'URL.
4. Dans GitHub → **Settings → Secrets and variables → Actions** :

   | Type | Nom | Valeur |
   |---|---|---|
   | Secret | `RENDER_DEPLOY_HOOK_URL` | l'URL du Deploy Hook (obligatoire pour déployer) |
   | Secret | `RENDER_API_KEY` | clé API Render (optionnel : suivi du déploiement) |
   | Variable | `RENDER_SERVICE_ID` | l'identifiant `srv-…` du service (optionnel, avec la clé API) |
   | Variable | `RENDER_APP_URL` | `https://nutritrace.onrender.com` (vérification finale) |

### Ensuite, à chaque fusion dans `main`

Le pipeline teste, construit l'image, la vérifie, la publie sur GitHub Container Registry
(`ghcr.io/<owner>/nutritrace`) puis **déclenche Render**. Le déploiement automatique de Render est
désactivé (`autoDeploy: false`) : **une version qui échoue aux tests n'est jamais déployée**.

Sur les autres branches (ex. `aziz-produit`), les étapes 1 à 3 tournent, mais pas le déploiement.

### Limites à connaître

- Sur l'offre gratuite, le service web s'endort après 15 min sans visite (premier chargement lent).
- Le disque du service web est éphémère : les **photos téléversées** sont perdues à chaque redéploiement
  (les illustrations de catégories prennent alors le relais). Pour les conserver : ajouter un disque au
  service web (offre payante) ou un stockage S3.
