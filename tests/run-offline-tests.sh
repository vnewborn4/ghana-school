#!/usr/bin/env bash
#
# Set up and run the browser tests for offline academy access.
#
#   tests/run-offline-tests.sh
#
# Rebuilds the development database, onboards a learner with a known PIN,
# publishes an assignment that takes a file, and runs tests/offline.test.js
# against a running dev server.
#
# DESTRUCTIVE: it rebuilds the database, so it needs ALLOW_DB_RESET=yes for
# the same reason tests/reset-dev-db.sh does.
#
#   BASE_URL   default http://localhost:8099 -- must be localhost, not an IP,
#              because only localhost is a secure context and without one the
#              browser has no service workers to test.

set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8099}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
export ROOT_DIR

case "$BASE_URL" in
  http://localhost*|https://*) ;;
  *) echo "BASE_URL must be https or http://localhost — service workers need a secure context."; exit 1 ;;
esac

if ! curl -sf -o /dev/null "$BASE_URL/index.php"; then
  echo "No server answering at $BASE_URL. Start one first, for example:"
  echo "  php -S localhost:8099 -t $ROOT_DIR"
  exit 1
fi

ALLOW_DB_RESET="${ALLOW_DB_RESET:-}" "$ROOT_DIR/tests/reset-dev-db.sh"

echo "Onboarding a learner"
JAR="$(mktemp)"
csrf() { curl -s -b "$JAR" -c "$JAR" "$1" | grep -oP 'name="csrf" value="\K[^"]+' | head -1; }

TOKEN="$(csrf "$BASE_URL/login.php")"
curl -s -b "$JAR" -c "$JAR" -o /dev/null \
  -d "csrf=$TOKEN" -d "email=teacher@example.org" -d "password=TestTeach!2026" "$BASE_URL/login.php"

TOKEN="$(csrf "$BASE_URL/teach/onboard.php")"
curl -s -b "$JAR" -c "$JAR" -o /dev/null \
  --data-urlencode "csrf=$TOKEN" --data-urlencode "mode=single" \
  --data-urlencode "display_name=Ama" --data-urlencode "age_band=8-10" \
  --data-urlencode "guardian_consent_on=2026-09-14" \
  --data-urlencode "consent_scope=learning_and_sponsor_updates" \
  "$BASE_URL/teach/onboard.php"
curl -s -b "$JAR" -c "$JAR" -o /dev/null "$BASE_URL/teach/cards.php"   # clears the one-time PINs
rm -f "$JAR"

# A known PIN, and no forced change, so the browser test can sign straight in.
# Also publish a module whose assignment takes a file, for the upload check.
LEARNER="$(php -r '
require getenv("ROOT_DIR") . "/includes/db.php";
$row = db()->query("SELECT username FROM learners ORDER BY id DESC LIMIT 1")->fetchColumn();
db()->prepare("UPDATE learners SET pin_hash=?, must_change_pin=0 WHERE username=?")
    ->execute([password_hash("4821", PASSWORD_DEFAULT), $row]);
db()->exec("UPDATE modules SET published=1 WHERE slug=\"blockly-maze\"");
echo $row;
')"
echo "  learner $LEARNER, PIN 4821"

echo "Running the browser tests"
BASE_URL="$BASE_URL" LEARNER="$LEARNER" PIN=4821 node "$ROOT_DIR/tests/offline.test.js"
