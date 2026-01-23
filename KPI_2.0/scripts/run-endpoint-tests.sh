#!/usr/bin/env bash
# Lightweight curl-based endpoint smoke tests for VISTA KPI
# Usage: ./scripts/run-endpoint-tests.sh https://app.example.com [PHPSESSID] [CSRF_TOKEN]

BASE_URL=${1:-http://127.0.0.1:8000}
COOKIE=${2:-}
CSRF=${3:-}
LOG_DIR="logs"
mkdir -p "$LOG_DIR"

function do_get() {
  local path="$1"
  echo "GET $BASE_URL$path"
  if [ -n "$COOKIE" ]; then
    curl -s -D - -b "$COOKIE" "$BASE_URL$path" -o "$LOG_DIR/resp_$(echo "$path" | sed 's/[^a-zA-Z0-9]/_/g').body" | sed -n '1,20p'
  else
    curl -s -D - "$BASE_URL$path" -o "$LOG_DIR/resp_$(echo "$path" | sed 's/[^a-zA-Z0-9]/_/g').body" | sed -n '1,20p'
  fi
  echo
}

function do_post() {
  local path="$1"
  shift
  echo "POST $BASE_URL$path"
  if [ -n "$COOKIE" ]; then
    curl -s -D - -b "$COOKIE" -X POST -F "$@" "$BASE_URL$path" -o "$LOG_DIR/resp_post_$(echo "$path" | sed 's/[^a-zA-Z0-9]/_/g').body" | sed -n '1,20p'
  else
    curl -s -D - -X POST -F "$@" "$BASE_URL$path" -o "$LOG_DIR/resp_post_$(echo "$path" | sed 's/[^a-zA-Z0-9]/_/g').body" | sed -n '1,20p'
  fi
  echo
}

# Tests
# 1) inventario-api list
if [ -n "$CSRF" ]; then
  do_get "/inventario-api?action=list&armario=ARM-01"
else
  do_get "/inventario-api?action=list&armario=ARM-01"
fi

# 2) main inventario page
do_get "/inventario"

# 3) favicon
do_get "/favicon.ico"

echo "Logs saved to $LOG_DIR"
