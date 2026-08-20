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
SSH="$OAV_SSH_USER@$OAV_SSH_HOST"
EXCLUDE=(--exclude='.git' --exclude='node_modules' --exclude='.DS_Store' --exclude='.env' --exclude='_backup-templates' --exclude='.claude' --exclude='.vscode' --exclude='.idea')

# ── 1. Pull server → local (patterns, parts, templates) ────────────────────
echo "Pulling server files to local (parts, templates, patterns)..."
rsync -avz --ignore-existing \
  "$SSH:$OAV_SSH_PATH/dc26-oav/parts/" "$THEME_DIR/parts/"
rsync -avz --ignore-existing \
  "$SSH:$OAV_SSH_PATH/dc26-oav/templates/" "$THEME_DIR/templates/"
rsync -avz --ignore-existing \
  "$SSH:$OAV_SSH_PATH/dc26-oav/patterns/" "$THEME_DIR/patterns/" 2>/dev/null || true
rsync -avz --ignore-existing \
  "$SSH:$OAV_SSH_PATH/dc26-oav/assets/fonts/" "$THEME_DIR/assets/fonts/" 2>/dev/null || true

# ── 2. Commit ───────────────────────────────────────────────────────────────
echo "Committing dc26-oav..."
cd "$THEME_DIR"
if ! git diff --quiet || ! git diff --cached --quiet || [ -n "$(git ls-files --others --exclude-standard)" ]; then
  read -r -p "Message de commit : " COMMIT_MSG
  git add -A
  git commit -m "${COMMIT_MSG:-deploy: $(date '+%Y-%m-%d %H:%M')}"
  git push
else
  echo "Rien à commiter."
fi

# ── 3. Build ────────────────────────────────────────────────────────────────
echo "Building dc26-oav..."
npm run build

# ── 4. Deploy (sans --delete) ───────────────────────────────────────────────
echo "Deploying dc26-base..."
rsync -avz "${EXCLUDE[@]}" "$THEMES_DIR/dc26-base/" "$REMOTE/dc26-base/"

echo "Deploying dc26-oav..."
rsync -avz "${EXCLUDE[@]}" "$THEME_DIR/" "$REMOTE/dc26-oav/"

echo "Done — https://oav.ch"
