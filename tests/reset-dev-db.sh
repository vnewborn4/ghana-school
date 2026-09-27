#!/usr/bin/env bash
#
# Rebuild a development database and seed the accounts tests/smoke.sh expects.
#
#   tests/reset-dev-db.sh
#
# DESTRUCTIVE: it drops the database named by GHANA_DB_NAME and recreates it.
# It refuses to run unless ALLOW_DB_RESET=yes, so it cannot be run by accident
# and can never be aimed at production by a stray environment variable.
#
#   GHANA_DB_NAME     database to rebuild   (default: ghana_school)
#   GHANA_DB_USER     MySQL user            (default: root)
#   GHANA_DB_PASSWORD MySQL password        (default: empty)
#   GHANA_DB_HOST     MySQL host            (default: 127.0.0.1)

set -euo pipefail

DB_NAME="${GHANA_DB_NAME:-ghana_school}"
DB_USER="${GHANA_DB_USER:-root}"
DB_PASS="${GHANA_DB_PASSWORD:-}"
DB_HOST="${GHANA_DB_HOST:-127.0.0.1}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
export ROOT_DIR   # the seeding step below reads it from the environment

if [ "${ALLOW_DB_RESET:-}" != "yes" ]; then
  echo "This drops and rebuilds the '$DB_NAME' database."
  echo "Re-run with ALLOW_DB_RESET=yes if that is what you want."
  exit 1
fi

mysql_do() {
  if [ -n "$DB_PASS" ]; then
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$@"
  else
    mysql -h "$DB_HOST" -u "$DB_USER" "$@"
  fi
}

echo "Rebuilding $DB_NAME"
mysql_do -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
mysql_do < "$ROOT_DIR/database.sql"

for file in "$ROOT_DIR"/migrations/*.sql; do
  printf '  %s ... ' "$(basename "$file")"
  if mysql_do "$DB_NAME" < "$file" 2>/dev/null; then echo "ok"; else echo "FAILED"; exit 1; fi
done

echo "Seeding accounts"
GHANA_DB_NAME="$DB_NAME" GHANA_DB_USER="$DB_USER" GHANA_DB_PASSWORD="$DB_PASS" GHANA_DB_HOST="$DB_HOST" \
php -r '
require getenv("ROOT_DIR") . "/includes/db.php";
$people = [
  ["Ama","Mensah","admin@example.org","TestAdmin!2026",1,"admin"],
  ["Kofi","Boateng","teacher@example.org","TestTeach!2026",0,"teacher"],
  ["Nana","Owusu","sponsor@example.org","TestSpon!2026",0,"sponsor"],
];
foreach ($people as $p) {
  db()->prepare("INSERT INTO users (first_name,last_name,email,password_hash,is_admin,role) VALUES (?,?,?,?,?,?)")
      ->execute([$p[0],$p[1],$p[2],password_hash($p[3],PASSWORD_DEFAULT),$p[4],$p[5]]);
}
db()->prepare("INSERT INTO cohorts (name,term,active) VALUES (?,?,1)")->execute(["Tuesday Coders","Term 1 2026"]);
$journey = db()->query("SELECT id FROM student_journeys ORDER BY id LIMIT 1")->fetchColumn();
$sponsor = db()->query("SELECT id FROM users WHERE email = \"sponsor@example.org\"")->fetchColumn();
db()->prepare("INSERT INTO sponsorships (user_id,student_journey_id,amount,frequency,status) VALUES (?,?,50.00,\"monthly\",\"active\")")
    ->execute([$sponsor,$journey]);
echo "  admin@example.org, teacher@example.org, sponsor@example.org\n";
'

# Learner files from a previous run would otherwise outlive their database rows.
STORAGE="${GHANA_STORAGE_PATH:-$ROOT_DIR/storage}"
for dir in student_sites submissions; do
  if [ -d "$STORAGE/$dir" ]; then
    rm -rf "${STORAGE:?}/${dir:?}"
    echo "  cleared $dir"
  fi
done

echo "Ready. Now: BASE_URL=... tests/smoke.sh"
