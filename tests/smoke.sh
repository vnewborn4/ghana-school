#!/usr/bin/env bash
#
# End-to-end smoke test for the student academy.
#
# Exercises the whole flow a real term goes through: staff sign-in, role
# separation, bulk onboarding, a learner's first sign-in and forced PIN change,
# editing and previewing their page, the review-and-publish gate, and the
# take-everything-offline switch. It also asserts the properties that protect
# children, so a regression in any of them fails the build rather than
# quietly shipping.
#
# Usage:
#   BASE_URL=http://localhost/ghana-school tests/smoke.sh
#
# Expects a database with the migrations applied and three seeded accounts:
#   admin@example.org   / TestAdmin!2026   role=admin
#   teacher@example.org / TestTeach!2026   role=teacher
#   sponsor@example.org / TestSpon!2026    role=sponsor, with an active
#                                          sponsorship of a student journey
# and at least one cohort. Run it against a development database only: it
# creates learners, publishes pages, and publishes sponsor updates.

set -uo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8099}"
HOST_HEADER="${HOST_HEADER:-}"
JAR_DIR="$(mktemp -d)"
trap 'rm -rf "${JAR_DIR:?}"' EXIT

FAILED=0
CURL=(curl -s)
[ -n "$HOST_HEADER" ] && CURL+=(-H "Host: $HOST_HEADER")

req()  { "${CURL[@]}" "$@"; }
code() { "${CURL[@]}" -o /dev/null -w '%{http_code}' "$@"; }
tok()  { "${CURL[@]}" -b "$1" -c "$1" "$2" | grep -oP 'name="csrf" value="\K[^"]+' | head -1; }
chk()  {
  if [ "$2" = "$3" ]; then printf '  PASS  %s\n' "$1"
  else printf '  FAIL  %s (expected %s, got %s)\n' "$1" "$3" "$2"; FAILED=1; fi
}
# For counts that may legitimately exceed the minimum, such as a page that
# renders one block per pending draft.
chk_min() {
  if [ "${2:-x}" -ge "$3" ] 2>/dev/null; then printf '  PASS  %s\n' "$1"
  else printf '  FAIL  %s (expected at least %s, got %s)\n' "$1" "$3" "$2"; FAILED=1; fi
}

echo "Smoke test against $BASE_URL"

echo "-- roles --"
T=$(tok "$JAR_DIR/t.jar" "$BASE_URL/login.php")
req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" -o /dev/null \
    -d "csrf=$T" -d "email=teacher@example.org" -d "password=TestTeach!2026" "$BASE_URL/login.php"
chk "teacher reaches the teacher portal" "$(code -b "$JAR_DIR/t.jar" "$BASE_URL/teach/index.php")" "200"
chk "teacher is refused sponsor admin"   "$(code -b "$JAR_DIR/t.jar" "$BASE_URL/admin.php")" "403"
chk "anonymous visitor is redirected"    "$(code "$BASE_URL/teach/index.php")" "302"

echo "-- bulk onboarding --"
T=$(tok "$JAR_DIR/t.jar" "$BASE_URL/teach/onboard.php")
ROSTER=$'Ama, 8-10, 2026-09-14, learning_and_sponsor_updates, girl\nKwesi, 11-12, 2026-09-14, learning_only, boy\nAdjoa, 8-10, 2026-09-15, learning_and_sponsor_updates, girl'
req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" -o /dev/null \
    --data-urlencode "csrf=$T" --data-urlencode "mode=bulk" --data-urlencode "roster=$ROSTER" \
    "$BASE_URL/teach/onboard.php"
CARDS=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" "$BASE_URL/teach/cards.php")
chk "three welcome cards printed" "$(echo "$CARDS" | grep -c 'welcome-card')" "3"
USERNAME=$(echo "$CARDS" | grep -oP '<dd>\K[a-z0-9]+-[a-z][0-9]' | sed -n 1p)
PIN=$(echo "$CARDS" | grep -oP '<dd>\K[0-9]{4}(?=</dd>)' | sed -n 1p)
# The second learner's guardian chose learning only -- used below to prove
# that consent, not the form, decides what can be shared.
USERNAME2=$(echo "$CARDS" | grep -oP '<dd>\K[a-z0-9]+-[a-z][0-9]' | sed -n 2p)
PIN2=$(echo "$CARDS" | grep -oP '<dd>\K[0-9]{4}(?=</dd>)' | sed -n 2p)
chk "a username was issued" "$([ -n "$USERNAME" ] && echo yes)" "yes"
chk "a one-time PIN was issued" "$([ -n "$PIN" ] && echo yes)" "yes"
chk "PINs are not shown twice" \
    "$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" "$BASE_URL/teach/cards.php" | grep -c 'welcome-card')" "0"

echo "-- learner sign-in --"
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/login.php")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null \
    -d "csrf=$T" -d "username=$USERNAME" -d "pin=$PIN" "$BASE_URL/academy/login.php"
chk "first sign-in forces a new PIN" \
    "$("${CURL[@]}" -b "$JAR_DIR/k.jar" -o /dev/null -w '%{redirect_url}' "$BASE_URL/academy/index.php" | grep -c 'change_pin.php')" "1"
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/change_pin.php")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null \
    -d "csrf=$T" -d "pin=1234" -d "pin_confirm=1234" "$BASE_URL/academy/change_pin.php"
chk "an obvious PIN is refused" \
    "$(req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -d "csrf=$T" -d "pin=1234" -d "pin_confirm=1234" "$BASE_URL/academy/change_pin.php" | grep -c 'notice-bad')" "1"
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/change_pin.php")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null \
    -d "csrf=$T" -d "pin=7391" -d "pin_confirm=7391" "$BASE_URL/academy/change_pin.php"
chk "dashboard opens" "$(code -b "$JAR_DIR/k.jar" "$BASE_URL/academy/index.php")" "200"

echo "-- the learner's page --"
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/mysite.php")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null \
    --data-urlencode "csrf=$T" --data-urlencode "action=save" \
    --data-urlencode 'html=<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="style.css"></head><body><h1>I build things</h1></body></html>' \
    --data-urlencode 'css=body{background:#102a24}' --data-urlencode 'js=' "$BASE_URL/academy/mysite.php"
chk "the owner can preview their draft" "$(code -b "$JAR_DIR/k.jar" "$BASE_URL/students/preview/$USERNAME/")" "200"
chk "an unpublished page is not public"  "$(code "$BASE_URL/students/$USERNAME/")" "404"
chk "a stranger cannot read the draft"   "$(code "$BASE_URL/students/preview/$USERNAME/")" "403"

echo "-- path traversal --"
for probe in "../../../includes/db.php" "..%2F..%2Fdb.php" "/etc/passwd"; do
  chk "traversal refused: $probe" "$(code "$BASE_URL/students/serve.php?s=$USERNAME&f=$probe")" "404"
done

echo "-- CSRF --"
chk "save without a token is refused" \
    "$(code -b "$JAR_DIR/k.jar" -d "action=save" -d "html=x" "$BASE_URL/academy/mysite.php")" "400"

echo "-- publication gate --"
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/mysite.php")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null -d "csrf=$T" -d "action=request_review" "$BASE_URL/academy/mysite.php"
SITE_ROW=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" "$BASE_URL/teach/pages.php")
SITE_ID=$(echo "$SITE_ROW" | grep -oP 'name="site_id" value="\K[0-9]+' | head -1)
T=$(tok "$JAR_DIR/t.jar" "$BASE_URL/teach/pages.php")
chk "a teacher may not publish" \
    "$(code -b "$JAR_DIR/t.jar" -d "csrf=$T" -d "site_id=$SITE_ID" -d "action=publish" "$BASE_URL/teach/pages.php")" "403"
T=$(tok "$JAR_DIR/a.jar" "$BASE_URL/login.php")
req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" -o /dev/null \
    -d "csrf=$T" -d "email=admin@example.org" -d "password=TestAdmin!2026" "$BASE_URL/login.php"
T=$(tok "$JAR_DIR/a.jar" "$BASE_URL/teach/pages.php")
req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" -o /dev/null \
    -d "csrf=$T" -d "site_id=$SITE_ID" -d "action=publish" "$BASE_URL/teach/pages.php"
chk "an admin can publish"            "$(code "$BASE_URL/students/$USERNAME/")" "200"
chk "relative assets resolve"         "$(code "$BASE_URL/students/$USERNAME/style.css")" "200"
chk "student code gets an opaque origin" \
    "$("${CURL[@]}" -D- -o /dev/null "$BASE_URL/students/$USERNAME/" | grep -ci 'Content-Security-Policy: sandbox')" "1"

echo "-- editing after publication does not change the live page --"
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/mysite.php")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null \
    --data-urlencode "csrf=$T" --data-urlencode "action=save" \
    --data-urlencode 'html=<h1>UNREVIEWED</h1>' --data-urlencode 'css=' --data-urlencode 'js=' \
    "$BASE_URL/academy/mysite.php"
chk "unreviewed edit stays out of the live copy" \
    "$(req "$BASE_URL/students/$USERNAME/" | grep -c 'UNREVIEWED')" "0"

echo "-- sponsor bridge --"
# The learner is linked to a journey by an administrator; without that link no
# classroom work can ever become a sponsor update.
ROSTER=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" "$BASE_URL/teach/index.php")
LEARNER_ID=$(echo "$ROSTER" | tr '\n' ' ' | grep -oP "<code>$USERNAME</code>.*?learner\.php\?id=\K[0-9]+" | head -1)
LEARNER_PAGE=$(req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" "$BASE_URL/teach/learner.php?id=$LEARNER_ID")
JOURNEY_ID=$(echo "$LEARNER_PAGE" | grep -oP '<option value="\K[1-9][0-9]*(?="(?![^>]*disabled))' | head -1)
T=$(tok "$JAR_DIR/a.jar" "$BASE_URL/teach/learner.php?id=$LEARNER_ID")
req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" -o /dev/null \
    -d "csrf=$T" -d "action=set_journey" -d "student_journey_id=$JOURNEY_ID" \
    "$BASE_URL/teach/learner.php?id=$LEARNER_ID"
chk "a teacher cannot link a journey" \
    "$(code -b "$JAR_DIR/t.jar" -d "csrf=$(tok "$JAR_DIR/t.jar" "$BASE_URL/teach/learner.php?id=$LEARNER_ID")" \
        -d "action=set_journey" -d "student_journey_id=$JOURNEY_ID" "$BASE_URL/teach/learner.php?id=$LEARNER_ID")" "403"

# A learner submits work containing details they should not have shared, so we
# can assert the draft is composed from lesson facts and not from their text.
ASSIGNMENT_ID=$(req -b "$JAR_DIR/k.jar" "$BASE_URL/academy/index.php" | grep -oP 'assignment\.php\?id=\K[0-9]+' | head -1)
T=$(tok "$JAR_DIR/k.jar" "$BASE_URL/academy/assignment.php?id=$ASSIGNMENT_ID")
req -b "$JAR_DIR/k.jar" -c "$JAR_DIR/k.jar" -o /dev/null \
    --data-urlencode "csrf=$T" --data-urlencode "action=submit" \
    --data-urlencode "body=I saved my work. My name is Ama Boateng and I go to Osu Primary." \
    "$BASE_URL/academy/assignment.php?id=$ASSIGNMENT_ID"
SUB_ID=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" "$BASE_URL/teach/marking.php" | grep -oP 'marking\.php\?id=\K[0-9]+' | head -1)
T=$(tok "$JAR_DIR/t.jar" "$BASE_URL/teach/marking.php?id=$SUB_ID")
MARKED=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" \
    -d "csrf=$T" -d "submission_id=$SUB_ID" -d "decision=reviewed" -d "shareable=1" \
    -d "teacher_feedback=Clearly explained." "$BASE_URL/teach/marking.php")
chk "reviewed shareable work drafts an update" "$(echo "$MARKED" | grep -c 'draft sponsor update')" "1"

QUEUE=$(req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" "$BASE_URL/teach/updates.php")
chk "the queue is administrators only" "$(code -b "$JAR_DIR/t.jar" "$BASE_URL/teach/updates.php")" "403"
DRAFT_SUMMARY=$(echo "$QUEUE" | grep -oP '<textarea[^>]*name="summary"[^>]*>\K[^<]*' | head -1)
chk "the draft does not carry the child's surname" "$(echo "$DRAFT_SUMMARY" | grep -ci 'Boateng')" "0"
chk "the draft does not carry their school"        "$(echo "$DRAFT_SUMMARY" | grep -ci 'Osu Primary')" "0"
chk_min "their words are shown to the admin as context" "$(echo "$QUEUE" | grep -c 'not published')" "1"

# Only after an administrator rewrites and approves does a sponsor see anything.
T=$(tok "$JAR_DIR/s.jar" "$BASE_URL/login.php")
req -b "$JAR_DIR/s.jar" -c "$JAR_DIR/s.jar" -o /dev/null \
    -d "csrf=$T" -d "email=sponsor@example.org" -d "password=TestSpon!2026" "$BASE_URL/login.php"
chk "a draft is invisible to the sponsor" \
    "$(req -b "$JAR_DIR/s.jar" "$BASE_URL/portal.php" | grep -c 'SMOKE TEST UPDATE')" "0"

UPDATE_ID=$(echo "$QUEUE" | grep -oP 'name="update_id" value="\K[0-9]+' | head -1)
T=$(tok "$JAR_DIR/a.jar" "$BASE_URL/teach/updates.php")
req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" -o /dev/null \
    -d "csrf=$T" -d "action=approve" -d "update_id=$UPDATE_ID" \
    --data-urlencode "title=SMOKE TEST UPDATE" --data-urlencode "milestone=First project" \
    --data-urlencode "summary=They finished their first guided project and explained one choice to the group." \
    "$BASE_URL/teach/updates.php"
chk "an approved update reaches the sponsor" \
    "$(req -b "$JAR_DIR/s.jar" "$BASE_URL/portal.php" | grep -c 'SMOKE TEST UPDATE')" "1"

T=$(tok "$JAR_DIR/a.jar" "$BASE_URL/teach/updates.php")
req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" -o /dev/null \
    -d "csrf=$T" -d "action=withdraw" -d "update_id=$UPDATE_ID" "$BASE_URL/teach/updates.php"
chk "a withdrawn update disappears again" \
    "$(req -b "$JAR_DIR/s.jar" "$BASE_URL/portal.php" | grep -c 'SMOKE TEST UPDATE')" "0"

echo "-- consent, not the form, decides --"
T=$(tok "$JAR_DIR/k2.jar" "$BASE_URL/academy/login.php")
req -b "$JAR_DIR/k2.jar" -c "$JAR_DIR/k2.jar" -o /dev/null \
    -d "csrf=$T" -d "username=$USERNAME2" -d "pin=$PIN2" "$BASE_URL/academy/login.php"
T=$(tok "$JAR_DIR/k2.jar" "$BASE_URL/academy/change_pin.php")
req -b "$JAR_DIR/k2.jar" -c "$JAR_DIR/k2.jar" -o /dev/null \
    -d "csrf=$T" -d "pin=8264" -d "pin_confirm=8264" "$BASE_URL/academy/change_pin.php"
A2=$(req -b "$JAR_DIR/k2.jar" "$BASE_URL/academy/index.php" | grep -oP 'assignment\.php\?id=\K[0-9]+' | head -1)
T=$(tok "$JAR_DIR/k2.jar" "$BASE_URL/academy/assignment.php?id=$A2")
req -b "$JAR_DIR/k2.jar" -c "$JAR_DIR/k2.jar" -o /dev/null \
    --data-urlencode "csrf=$T" --data-urlencode "action=submit" \
    --data-urlencode "body=I finished it." "$BASE_URL/academy/assignment.php?id=$A2"
SUB2=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" "$BASE_URL/teach/marking.php" | grep -oP 'marking\.php\?id=\K[0-9]+' | head -1)
T=$(tok "$JAR_DIR/t.jar" "$BASE_URL/teach/marking.php?id=$SUB2")
# The teacher posts shareable=1 anyway. The guardian chose learning only, so
# the query must ignore it.
MARKED2=$(req -b "$JAR_DIR/t.jar" -c "$JAR_DIR/t.jar" \
    -d "csrf=$T" -d "submission_id=$SUB2" -d "decision=reviewed" -d "shareable=1" "$BASE_URL/teach/marking.php")
chk "a forced shareable flag drafts nothing" "$(echo "$MARKED2" | grep -c 'draft sponsor update')" "0"

echo "-- public figures are aggregates only --"
IMPACT=$(req "$BASE_URL/impact.php")
chk "figures are shown"              "$(echo "$IMPACT" | grep -c 'Learners enrolled')" "1"
chk "no learner is named publicly"   "$(echo "$IMPACT" | grep -ci 'Boateng\|Osu Primary')" "0"

echo "-- take everything offline --"
T=$(tok "$JAR_DIR/a.jar" "$BASE_URL/teach/pages.php")
req -b "$JAR_DIR/a.jar" -c "$JAR_DIR/a.jar" -o /dev/null -d "csrf=$T" -d "action=unpublish_all" "$BASE_URL/teach/pages.php"
chk "every page is offline" "$(code "$BASE_URL/students/$USERNAME/")" "404"

echo
if [ "$FAILED" = "1" ]; then echo "SMOKE TEST FAILED"; exit 1; fi
echo "SMOKE TEST PASSED"
