#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSE_FILE="docker-compose.prod.yml"
APP_CONTAINER="entropiq-prod-app"

cd "$PROJECT_DIR"

echo "=== Deploying Entropiq (production) ==="

echo "[1/6] Pulling latest code..."
git pull origin main

echo "[2/6] Building Docker image..."
docker compose -f "$COMPOSE_FILE" build --no-cache app

echo "[3/6] Stopping current containers..."
docker compose -f "$COMPOSE_FILE" down

echo "[4/6] Starting containers..."
docker compose -f "$COMPOSE_FILE" up -d

echo "[5/6] Running post-deploy tasks..."
sleep 5
docker exec "$APP_CONTAINER" php artisan migrate --force
docker exec "$APP_CONTAINER" php artisan config:cache
docker exec "$APP_CONTAINER" php artisan route:cache
docker exec "$APP_CONTAINER" php artisan view:cache
docker exec "$APP_CONTAINER" php artisan event:cache
docker exec "$APP_CONTAINER" php artisan horizon:terminate

echo "[6/6] Health check..."
for i in $(seq 1 10); do
    if docker exec "$APP_CONTAINER" curl -sf http://localhost/up > /dev/null 2>&1; then
        echo "Application is healthy!"
        break
    fi
    if [ "$i" -eq 10 ]; then
        echo "WARNING: Health check failed after 10 attempts"
        docker compose -f "$COMPOSE_FILE" logs app --tail=50
        exit 1
    fi
    echo "  waiting... ($i/10)"
    sleep 3
done

echo "=== Deploy complete ==="
docker compose -f "$COMPOSE_FILE" ps
