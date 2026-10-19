#!/bin/sh
set -e

# Applique les migrations (SQLite par défaut) avant de démarrer le serveur
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"
