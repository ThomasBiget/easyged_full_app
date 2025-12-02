#!/bin/bash
PORT="${PORT:-8080}"

echo "Starting EasyGED API..."
echo "DB_HOST: $DB_HOST"
echo "DB_NAME: $DB_NAME"

# Exécute les migrations au démarrage
echo "Running database migrations..."
php database/migrations/create_tables.php 2>&1 || echo "Migration failed but continuing..."

echo "Starting PHP server on port $PORT"
php -S 0.0.0.0:$PORT -t public
