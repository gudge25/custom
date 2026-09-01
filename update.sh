#!/bin/bash
# Pull the latest code from GitHub onto this server.
# Usage: ./update.sh [--force]
#   --force   discard local modifications to tracked files and reset hard to origin/main

set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

FORCE=0
if [ "${1:-}" = "--force" ]; then
    FORCE=1
fi

echo "Repo: $REPO_DIR"
echo "Current commit: $(git rev-parse --short HEAD)"

git fetch origin main

LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "Already up to date."
    exit 0
fi

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    if [ "$FORCE" -ne 1 ]; then
        echo "Local modifications to tracked files detected — aborting to avoid losing them."
        echo "Review with 'git status', commit/stash your changes, or re-run with --force to discard them."
        exit 1
    fi
    echo "Local modifications detected — discarding (--force)."
fi

echo "Updating $LOCAL -> $REMOTE"
git reset --hard origin/main

echo "Changed files:"
git diff --name-only "$LOCAL" "$REMOTE"

echo "Now at: $(git rev-parse --short HEAD)"
