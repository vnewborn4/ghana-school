#!/usr/bin/env python3
"""
Report Kolibri activity from the centre to the Mill Creek-AR website.

Runs on the centre's Kolibri server in Accra. It exports Kolibri's own logs
with the supported `kolibri manage exportlogs` command, reduces them to one
row per learner per day, and either posts the result to the website or writes
it to a file for someone to carry on a USB stick.

Nothing here needs the internet except the --post step. A centre with no line
at all still produces the same file, and staff upload it in the teacher
portal; the website treats both routes identically.

What is sent is deliberately thin: a date, a Kolibri username (the same
pseudonymous slug the website already uses), and three counts. No content
titles, no real names, nothing a child typed.

Usage
  kolibri-sync.py --days 30 --out /media/usb/kolibri-sync.json
  kolibri-sync.py --days 7  --post
  kolibri-sync.py --days 7  --post --out /var/lib/kolibri-sync/last.json

Configuration, from the environment or /etc/kolibri-sync.env:
  SITE_SYNC_URL         https://millcreek-ar-learning.com/api/kolibri_sync.php
  KOLIBRI_SYNC_SECRET   the same secret set on the web server
  KOLIBRI_HOME          defaults to /var/lib/kolibri
  KOLIBRI_BIN           defaults to the kolibri on PATH
  CENTRE_DEVICE         a short name for this machine, e.g. centre-pi-01

Exit status is 0 on success, 1 on failure, so a systemd timer or cron job
reports honestly.
"""

import argparse
import csv
import datetime as dt
import hashlib
import hmac
import io
import json
import os
import shutil
import subprocess
import sys
import tempfile
import urllib.error
import urllib.request

DEFAULT_ENV_FILE = "/etc/kolibri-sync.env"


def load_env_file(path):
    """Read simple KEY=value lines, without overriding the real environment."""
    if not os.path.isfile(path):
        return
    try:
        with open(path, "r", encoding="utf-8") as handle:
            for line in handle:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, value = line.split("=", 1)
                os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))
    except OSError as exc:
        warn("could not read %s: %s" % (path, exc))


def warn(message):
    print("kolibri-sync: %s" % message, file=sys.stderr)


def die(message, code=1):
    warn(message)
    sys.exit(code)


def export_logs(kolibri_bin, kolibri_home, log_type, start, end, destination):
    """
    Run `kolibri manage exportlogs`.

    Kolibri 0.19 crashes if the date bounds are omitted -- it slices
    start_date while it is still None -- so both are always passed.
    """
    env = dict(os.environ, KOLIBRI_HOME=kolibri_home)
    command = [
        kolibri_bin, "manage", "exportlogs",
        "--log-type", log_type,
        "--output-file", destination,
        "--overwrite",
        "--start_date", start.strftime("%Y-%m-%dT00:00:00"),
        "--end_date", end.strftime("%Y-%m-%dT23:59:59"),
    ]
    result = subprocess.run(command, env=env, capture_output=True, text=True)
    if result.returncode != 0:
        tail = (result.stderr or result.stdout or "").strip().splitlines()[-6:]
        die("exportlogs (%s) failed:\n  %s" % (log_type, "\n  ".join(tail)))
    if not os.path.isfile(destination):
        die("exportlogs (%s) reported success but wrote no file" % log_type)
    return destination


def read_rows(path):
    """Kolibri writes a BOM; utf-8-sig strips it."""
    with open(path, "r", encoding="utf-8-sig", newline="") as handle:
        for row in csv.DictReader(handle):
            yield row


def day_of(timestamp):
    """Kolibri timestamps look like 2026-09-27 10:56:50.954122+00:00."""
    value = (timestamp or "").strip()
    if len(value) < 10:
        return None
    candidate = value[:10]
    try:
        dt.datetime.strptime(candidate, "%Y-%m-%d")
    except ValueError:
        return None
    return candidate


def collect(session_csv, summary_csv, start, end):
    """
    Reduce both log exports to one record per (date, username).

    Session logs carry one row per visit, so they give attendance and minutes.
    Summary logs are cumulative per learner and content item, so they are only
    used for completions, dated by when the completion actually happened.
    """
    rows = {}

    def slot(date, username):
        return rows.setdefault(
            (date, username),
            {"date": date, "username": username, "sessions": 0, "completed": 0, "seconds": 0.0},
        )

    low, high = start.strftime("%Y-%m-%d"), end.strftime("%Y-%m-%d")

    for row in read_rows(session_csv):
        if (row.get("User type") or "").strip().lower() != "learner":
            continue
        username = (row.get("Username") or "").strip().lower()
        date = day_of(row.get("Time of first interaction"))
        if not username or not date or not (low <= date <= high):
            continue
        record = slot(date, username)
        record["sessions"] += 1
        try:
            record["seconds"] += float(row.get("Time Spent (sec)") or 0)
        except ValueError:
            pass

    for row in read_rows(summary_csv):
        if (row.get("User type") or "").strip().lower() != "learner":
            continue
        username = (row.get("Username") or "").strip().lower()
        date = day_of(row.get("Time of completion"))
        if not username or not date or not (low <= date <= high):
            continue
        slot(date, username)["completed"] += 1

    out = []
    for record in rows.values():
        out.append({
            "date": record["date"],
            "username": record["username"],
            "sessions": record["sessions"],
            "completed": record["completed"],
            "minutes": int(round(record["seconds"] / 60.0)),
        })
    out.sort(key=lambda r: (r["date"], r["username"]))
    return out


def build_payload(rows, facility, device, start, end):
    return {
        "version": 1,
        "facility": facility,
        "device": device,
        "generated_at": dt.datetime.now(dt.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "window": {"from": start.strftime("%Y-%m-%d"), "to": end.strftime("%Y-%m-%d")},
        "rows": rows,
    }


def post_payload(url, secret, payload):
    """Sign the exact bytes that are sent; the server hashes the same bytes."""
    body = json.dumps(payload, separators=(",", ":"), sort_keys=True).encode("utf-8")
    signature = "sha256=" + hmac.new(secret.encode("utf-8"), body, hashlib.sha256).hexdigest()
    request = urllib.request.Request(
        url,
        data=body,
        method="POST",
        headers={
            "Content-Type": "application/json",
            "X-Signature": signature,
            "User-Agent": "kolibri-sync/1.0",
        },
    )
    try:
        with urllib.request.urlopen(request, timeout=60) as response:
            return json.loads(response.read().decode("utf-8"))
    except urllib.error.HTTPError as exc:
        detail = exc.read().decode("utf-8", "replace")[:400]
        die("the website refused the sync (HTTP %s): %s" % (exc.code, detail))
    except urllib.error.URLError as exc:
        die("could not reach the website: %s. The centre may be offline -- "
            "write the file with --out and carry it instead." % exc.reason)


def check_clock():
    """
    A Raspberry Pi has no battery-backed clock. If the date looks wrong the
    activity dates will be wrong too, which is worse than not syncing.
    """
    now = dt.datetime.now(dt.timezone.utc)
    if now.year < 2025:
        die("this machine's clock reads %s, which cannot be right. Fix the "
            "time before syncing, or every date in this report will be wrong."
            % now.strftime("%Y-%m-%d %H:%M"))


def main():
    load_env_file(os.environ.get("KOLIBRI_SYNC_ENV", DEFAULT_ENV_FILE))

    parser = argparse.ArgumentParser(description="Report Kolibri activity to the website.")
    parser.add_argument("--days", type=int, default=30, help="how many days back to report (default 30)")
    parser.add_argument("--out", help="write the payload to this file, for a USB stick")
    parser.add_argument("--post", action="store_true", help="send the payload to the website")
    parser.add_argument("--quiet", action="store_true", help="only report problems")
    args = parser.parse_args()

    if not args.post and not args.out:
        parser.error("choose --post, --out FILE, or both.")
    if args.days < 1 or args.days > 400:
        parser.error("--days must be between 1 and 400.")

    check_clock()

    kolibri_home = os.environ.get("KOLIBRI_HOME", "/var/lib/kolibri")
    kolibri_bin = os.environ.get("KOLIBRI_BIN") or shutil.which("kolibri")
    if not kolibri_bin:
        die("kolibri was not found. Set KOLIBRI_BIN to its full path.")
    device = os.environ.get("CENTRE_DEVICE", os.uname().nodename)[:60]
    facility = os.environ.get("CENTRE_FACILITY", "")

    end = dt.date.today()
    start = end - dt.timedelta(days=args.days - 1)

    workdir = tempfile.mkdtemp(prefix="kolibri-sync-")
    try:
        session_csv = export_logs(kolibri_bin, kolibri_home, "session", start, end,
                                  os.path.join(workdir, "session.csv"))
        summary_csv = export_logs(kolibri_bin, kolibri_home, "summary", start, end,
                                  os.path.join(workdir, "summary.csv"))
        rows = collect(session_csv, summary_csv, start, end)
    finally:
        shutil.rmtree(workdir, ignore_errors=True)

    payload = build_payload(rows, facility, device, start, end)

    if not args.quiet:
        learners = len({r["username"] for r in rows})
        print("kolibri-sync: %s to %s — %d rows, %d learners, %d completions"
              % (payload["window"]["from"], payload["window"]["to"], len(rows),
                 learners, sum(r["completed"] for r in rows)))

    if args.out:
        directory = os.path.dirname(os.path.abspath(args.out))
        if directory and not os.path.isdir(directory):
            die("no such directory: %s. Is the USB stick plugged in?" % directory)
        # Write then move, so a stick pulled mid-write cannot leave half a file.
        temp_path = args.out + ".part"
        with io.open(temp_path, "w", encoding="utf-8") as handle:
            json.dump(payload, handle, separators=(",", ":"), sort_keys=True)
        os.replace(temp_path, args.out)
        if not args.quiet:
            print("kolibri-sync: wrote %s" % args.out)

    if args.post:
        url = os.environ.get("SITE_SYNC_URL", "")
        secret = os.environ.get("KOLIBRI_SYNC_SECRET", "")
        if not url:
            die("SITE_SYNC_URL is not set.")
        if not secret:
            die("KOLIBRI_SYNC_SECRET is not set. It must match the website.")
        result = post_payload(url, secret, payload)
        if not result.get("ok"):
            die("the website rejected the sync: %s" % result.get("error", "no reason given"))
        if not args.quiet:
            print("kolibri-sync: sent — %s rows recorded, %s matched to a learner account"
                  % (result.get("received"), result.get("matched")))

    return 0


if __name__ == "__main__":
    sys.exit(main())
