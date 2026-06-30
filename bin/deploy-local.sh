#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

SOURCE_PATH="${SOURCE_PATH:-$REPO_ROOT}"
CONTAINER_NAME="${CONTAINER_NAME:-demo-webserver-1}"
TARGET_PATH="${TARGET_PATH:-}"

usage() {
  cat <<EOF
Usage: $(basename "$0") [--source <path>] [--target <path>]

Deploy the local_customerportal plugin into the local Moodle checkout.

Options:
  --source <path>   Source plugin repository path
  --target <path>   Target Moodle plugin path
  --help            Show this help

Environment:
  SOURCE_PATH       Alternative source path
  TARGET_PATH       Alternative target path
  CONTAINER_NAME    Docker container used for mount auto-detection
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --source)
      SOURCE_PATH="$2"
      shift 2
      ;;
    --target)
      TARGET_PATH="$2"
      shift 2
      ;;
    --help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

SOURCE_PATH="$(cd "$SOURCE_PATH" && pwd)"

if [[ -z "$TARGET_PATH" ]]; then
  if command -v docker >/dev/null 2>&1 && docker inspect "$CONTAINER_NAME" >/dev/null 2>&1; then
    TARGET_PATH="$(docker inspect "$CONTAINER_NAME" --format '{{range .Mounts}}{{if eq .Destination "/var/www/site"}}{{.Source}}{{end}}{{end}}')/moodle/public/local/customerportal"
  else
    TARGET_PATH="/Users/moskaliuk/demo/site/moodle/public/local/customerportal"
  fi
fi

TARGET_PATH="$(mkdir -p "$TARGET_PATH" && cd "$TARGET_PATH" && pwd)"

echo "Deploying local_customerportal"
echo "  source: $SOURCE_PATH"
echo "  target: $TARGET_PATH"

rsync -a --delete \
  --exclude='.git' \
  --exclude='.DS_Store' \
  --exclude='.idea' \
  --exclude='.vscode' \
  --exclude='node_modules' \
  --exclude='vendor' \
  "$SOURCE_PATH/" "$TARGET_PATH/"

echo "Deploy complete."
