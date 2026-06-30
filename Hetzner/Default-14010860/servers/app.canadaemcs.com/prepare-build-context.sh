#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Repo root is 4 levels up: Hetzner/<project>/servers/<domain>/ (reorganized 2026-06)
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
SOURCE_DIR="$REPO_ROOT/Resources/moodle_plugins"
TARGET_DIR="$SCRIPT_DIR/moodleplugins"

required_archives=(
  "Edwiser-RemUI-v5.1.2.zip"
  "block_edwiseradvancedblock.zip"
  "filter_edwiserpbf.zip"
  "block_site_monitor.zip"
  "edwiservideoactivity.zip"
  "edwiservideoformat.zip"
  "moodle-local-edwiserreports-1.zip"
  "Edwiser-Forms-Pro.zip"
  "mod_interactivevideo.zip"
)

mkdir -p "$TARGET_DIR"

for archive in "${required_archives[@]}"; do
  source="$SOURCE_DIR/$archive"
  target="$TARGET_DIR/$archive"

  if [[ ! -f "$source" ]]; then
    echo "Missing plugin archive: $source" >&2
    exit 1
  fi

  if [[ ! -e "$target" ]]; then
    ln "$source" "$target" 2>/dev/null || cp -p "$source" "$target"
  fi
done

missing=0
while IFS= read -r source; do
  if [[ ! -e "$SCRIPT_DIR/$source" ]]; then
    echo "Missing Docker COPY source: $source" >&2
    missing=1
  fi
done < <(awk '/^[[:space:]]*COPY[[:space:]]/ { src=$2; gsub(/"/, "", src); if (src !~ /^--/) print src }' "$SCRIPT_DIR/Dockerfile")

if [[ "$missing" -ne 0 ]]; then
  exit 1
fi

echo "Build context ready: $SCRIPT_DIR"
