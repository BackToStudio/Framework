#!/usr/bin/env bash

# Split monorepo into individual read-only package repositories.
#
# Requirements:
#   - splitsh-lite (https://github.com/splitsh/lite)
#
# Usage:
#   ./bin/split.sh              # Split all packages (push to remotes)
#   ./bin/split.sh --dry-run    # Show what would be done without pushing
#
# Environment:
#   REMOTE_PREFIX - Git org/base URL for split repos (default: git@github.com:backto)

set -euo pipefail

REMOTE_PREFIX="${REMOTE_PREFIX:-git@github.com:backto}"
DRY_RUN=false

if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN=true
fi

# Map of directory → repository name
declare -A PACKAGES=(
    ["src/Admin"]="admin"
    ["src/Assets"]="assets"
    ["src/Blocks"]="blocks"
    ["src/Cache"]="cache"
    ["src/Cli"]="cli"
    ["src/Compose"]="compose"
    ["src/Contracts"]="contracts"
    ["src/Exception"]="exception"
    ["src/Hooks"]="hooks"
    ["src/Observability"]="observability"
    ["src/Options"]="options"
    ["src/Plugin"]="plugin"
    ["src/PostMeta"]="post-meta"
    ["src/PostType"]="post-type"
    ["src/RestApi"]="rest-api"
    ["src/Security"]="security"
    ["src/Seo"]="seo"
    ["src/Taxonomy"]="taxonomy"
    ["src/Theme"]="theme"
)

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

for path in "${!PACKAGES[@]}"; do
    repo="${PACKAGES[$path]}"
    remote_url="${REMOTE_PREFIX}/${repo}.git"

    echo "--- Splitting ${path} → ${repo}"

    SHA=$(./bin/splitsh-lite --prefix="${path}")

    if [[ "$DRY_RUN" == true ]]; then
        echo "    [dry-run] Would push ${SHA} to ${remote_url} (branch: ${CURRENT_BRANCH})"
    else
        git remote add "${repo}" "${remote_url}" 2>/dev/null || true
        git push "${repo}" "${SHA}:refs/heads/${CURRENT_BRANCH}" --force
    fi
done

echo "Done."
