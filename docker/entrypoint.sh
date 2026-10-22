#!/bin/sh
# ==========================================================================
# Démarrage du conteneur Nutritrace (Docker local, CI et Render)
# ==========================================================================
set -e

cd /var/www/html

# 1. Port d'écoute : Render impose la variable PORT (80 par défaut en local)
PORT="${PORT:-80}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. La clé de chiffrement Laravel est obligatoire. Sur Render, elle est dérivée du secret
#    APP_SECRET généré automatiquement par la plateforme (stable d'un déploiement à l'autre).
if [ -z "${APP_KEY}" ] && [ -n "${APP_SECRET}" ]; then
  APP_KEY=$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_SECRET"), true));')
  export APP_KEY
  echo "APP_KEY dérivée de APP_SECRET."
fi
if [ -z "${APP_KEY}" ]; then
  echo "ERREUR : la variable APP_KEY est vide. Générez-la avec : php artisan key:generate --show" >&2
  exit 1
fi

# 3. Base MySQL externe en TLS (Aiven…) : certificat CA fourni en texte dans DB_SSL_CA_PEM
if [ -n "${DB_SSL_CA_PEM}" ]; then
  printf '%s\n' "${DB_SSL_CA_PEM}" > /var/www/html/storage/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/var/www/html/storage/db-ca.pem
  echo "Connexion MySQL chiffrée (TLS) avec le certificat fourni."
fi

# 4. Attente de la base de données puis migrations (jusqu'à ~2 minutes)
echo "Attente de la base de données ${DB_HOST:-?}:${DB_PORT:-3306}..."
attempt=0
until php artisan migrate --force --no-interaction; do
  attempt=$((attempt + 1))
  if [ "$attempt" -ge 24 ]; then
    echo "ERREUR : base de données injoignable." >&2
    exit 1
  fi
  echo "Base pas encore prête (tentative ${attempt}/24), nouvel essai dans 5 s..."
  sleep 5
done

# 5. Données de démo (comptes de test, produits...) uniquement si la base est vide
if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
  php artisan app:seed-if-empty --no-interaction
fi

# 6. Lien public vers les photos des produits + caches de production
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache
php artisan event:cache

chown -R www-data:www-data storage bootstrap/cache

echo "Nutritrace prêt sur le port ${PORT}."
exec "$@"
