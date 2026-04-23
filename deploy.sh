#!/usr/bin/env bash
set -euo pipefail

THEME_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$THEME_DIR/../.." && pwd)"
THEMES_DIR="$(cd "$THEME_DIR/.." && pwd)"

# Load SSH credentials from root .env (safe parse — ignores values with spaces)
ENV_FILE="$ROOT_DIR/.env"
if [[ -f "$ENV_FILE" ]]; then
  while IFS='=' read -r key value; do
    [[ "$key" =~ ^OAV_SSH_ ]] && export "$key=$value"
  done < "$ENV_FILE"
else
  echo "ERROR: .env not found at $ENV_FILE" >&2; exit 1
fi

: "${OAV_SSH_USER:?OAV_SSH_USER not set in .env}"
: "${OAV_SSH_HOST:?OAV_SSH_HOST not set in .env}"
: "${OAV_SSH_PATH:?OAV_SSH_PATH not set in .env}"

REMOTE="$OAV_SSH_USER@$OAV_SSH_HOST:$OAV_SSH_PATH"
EXCLUDE=(--exclude='.git' --exclude='node_modules' --exclude='.DS_Store' --exclude='.env')

echo "Building dc26-oav..."
cd "$THEME_DIR" && npm run build

echo "Deploying dc26-base..."
rsync -avz --delete "${EXCLUDE[@]}" "$THEMES_DIR/dc26-base/" "$REMOTE/dc26-base/"

echo "Deploying dc26-oav..."
rsync -avz --delete "${EXCLUDE[@]}" "$THEME_DIR/" "$REMOTE/dc26-oav/"

echo "Done — https://dev.oav.ch"
