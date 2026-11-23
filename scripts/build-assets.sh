#!/usr/bin/env bash
# Usage: scripts/build-assets.sh
# Rebuilds the minified front-end assets and source maps from tracked sources.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SASS_BIN="$ROOT_DIR/node_modules/.bin/sass"
TERSER_BIN="$ROOT_DIR/node_modules/.bin/terser"
ESBUILD_BIN="$ROOT_DIR/node_modules/.bin/esbuild"

if [ ! -x "$SASS_BIN" ] || [ ! -x "$TERSER_BIN" ] || [ ! -x "$ESBUILD_BIN" ]; then
  npm ci
fi

THEME_CSS="app/modules/web/themes/material-blue/css"
THEME_JS="app/modules/web/themes/material-blue/js"
PUBLIC_JS="public/js"
PUBLIC_CSS="public/css"
VENDOR_JS="public/vendor/js"
VENDOR_CSS="public/vendor/css"

compile_scss() {
  local file base

  for file in "$THEME_CSS"/*.scss; do
    base="$(basename "$file" .scss)"

    case "$base" in
      _*) continue ;;
    esac

    "$SASS_BIN" --style=expanded "$file" "$THEME_CSS/$base.css"
  done
}

minify_css() {
  local source="$1"
  local output="$2"

  "$ESBUILD_BIN" "$source" --minify --sourcemap --outfile="$output" --log-level=warning
}

minify_js() {
  local source="$1"
  local output="$2"

  "$TERSER_BIN" "$source" \
    --compress \
    --mangle \
    --source-map "filename='$output.map',url='$(basename "$output").map'" \
    --output "$output"
}

minify_css_dir() {
  local dir="$1"
  local file base

  for file in "$dir"/*.css; do
    case "$file" in
      *.min.css) continue ;;
    esac

    base="$(basename "$file" .css)"
    minify_css "$file" "$dir/$base.min.css"
  done
}

minify_js_dir() {
  local dir="$1"
  local file base

  for file in "$dir"/*.js; do
    case "$file" in
      *.min.js) continue ;;
    esac

    base="$(basename "$file" .js)"
    minify_js "$file" "$dir/$base.min.js"
  done
}

compile_scss
minify_css_dir "$THEME_CSS"
minify_js_dir "$THEME_JS"
minify_js_dir "$PUBLIC_JS"
minify_js_dir "$VENDOR_JS"
minify_css_dir "$VENDOR_CSS"
minify_css "$THEME_CSS/fonts.css" "$PUBLIC_CSS/fonts.min.css"
