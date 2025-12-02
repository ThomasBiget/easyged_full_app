#!/bin/bash
PORT="${PORT:-8080}"

echo "🚀 Starting EasyGED API..."

# Exécute les migrations au démarrage
echo "📦 Running database migrations..."
php database/migrations/create_tables.php

echo "✅ Migrations completed!"
echo "🌐 Starting PHP server on port $PORT"

php -S 0.0.0.0:$PORT -t public
