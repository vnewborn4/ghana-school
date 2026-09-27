#!/usr/bin/env bash
#
# Set up the Mill Creek-AR learning centre's Kolibri server.
#
# Kolibri is an offline-first learning platform. Installed on a Raspberry Pi
# or a spare laptop at the centre, it serves lessons to every device on the
# centre's own Wi-Fi with no internet at all. This script installs it,
# provisions it for children aged 8-14, and sets up the reporting link back
# to the website.
#
# Tested against Kolibri 0.19.5 on Debian/Raspberry Pi OS/Ubuntu.
#
#   sudo ./install-kolibri.sh
#
# Safe to re-run: every step checks whether it has already been done.
#
# Overridable with environment variables:
#   KOLIBRI_USER     system user to run as        (default: kolibri)
#   KOLIBRI_HOME     data directory               (default: /var/lib/kolibri)
#   KOLIBRI_VENV     install directory            (default: /opt/kolibri)
#   FACILITY_NAME    name shown in Kolibri        (default: Mill Creek-AR Learning Center)
#   ADMIN_USERNAME   Kolibri super admin          (default: centreadmin)
#   HTTP_PORT        port learners connect to     (default: 8080)

set -euo pipefail

KOLIBRI_USER="${KOLIBRI_USER:-kolibri}"
KOLIBRI_HOME="${KOLIBRI_HOME:-/var/lib/kolibri}"
KOLIBRI_VENV="${KOLIBRI_VENV:-/opt/kolibri}"
FACILITY_NAME="${FACILITY_NAME:-Mill Creek-AR Learning Center}"
ADMIN_USERNAME="${ADMIN_USERNAME:-centreadmin}"
HTTP_PORT="${HTTP_PORT:-8080}"
ZIP_CONTENT_PORT="${ZIP_CONTENT_PORT:-8081}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

say()  { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
note() { printf '    %s\n' "$*"; }
die()  { printf '\n\033[31mERROR: %s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Run this with sudo."

# ---------------------------------------------------------------- packages --
say "Installing system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
# fake-hwclock keeps a sensible date across reboots on a Raspberry Pi, which
# has no battery-backed clock. Without it an offline Pi can come back believing
# it is 1970, and every activity date it reports would be wrong.
apt-get install -y -qq python3-venv python3-pip fake-hwclock ca-certificates
systemctl enable --now fake-hwclock 2>/dev/null || note "fake-hwclock not managed by systemd here"
systemctl enable --now systemd-timesyncd 2>/dev/null || note "systemd-timesyncd unavailable; set the clock by hand when offline"

# -------------------------------------------------------------------- user --
if id "$KOLIBRI_USER" >/dev/null 2>&1; then
  note "user $KOLIBRI_USER already exists"
else
  say "Creating the $KOLIBRI_USER user"
  useradd --system --create-home --home-dir "$KOLIBRI_HOME" --shell /usr/sbin/nologin "$KOLIBRI_USER"
fi
mkdir -p "$KOLIBRI_HOME"
chown -R "$KOLIBRI_USER":"$KOLIBRI_USER" "$KOLIBRI_HOME"

# ----------------------------------------------------------------- kolibri --
if [ -x "$KOLIBRI_VENV/bin/kolibri" ]; then
  note "Kolibri already installed: $("$KOLIBRI_VENV/bin/kolibri" --version 2>/dev/null | tail -1)"
else
  say "Installing Kolibri (this downloads about 100 MB)"
  python3 -m venv "$KOLIBRI_VENV"
  "$KOLIBRI_VENV/bin/pip" install --quiet --upgrade pip
  "$KOLIBRI_VENV/bin/pip" install --quiet kolibri
  note "installed $("$KOLIBRI_VENV/bin/kolibri" --version 2>/dev/null | tail -1)"
fi

# ----------------------------------------------------------------- options --
say "Writing $KOLIBRI_HOME/options.ini"
cat > "$KOLIBRI_HOME/options.ini" <<INI
[Deployment]
# The port learners connect to: http://<this machine>:$HTTP_PORT
HTTP_PORT = $HTTP_PORT

# Serve HTML5 content apps from a separate port so they run in their own
# origin and cannot reach the rest of Kolibri. Kolibri strongly recommends
# this, for the same reason the website sandboxes student pages.
ZIP_CONTENT_PORT = $ZIP_CONTENT_PORT

# Answer on every network interface, so tablets and phones on the centre's
# Wi-Fi can reach it.
LISTEN_ADDRESS = 0.0.0.0

# No statistics pingback. The centre is offline most of the time, and a
# failing call to an unreachable server just fills the log.
DISABLE_PING = True
INI
chown "$KOLIBRI_USER":"$KOLIBRI_USER" "$KOLIBRI_HOME/options.ini"

# -------------------------------------------------------------- provision ---
if [ -f "$KOLIBRI_HOME/db.sqlite3" ]; then
  note "already provisioned; leaving the facility and its accounts alone"
else
  say "Provisioning the facility"
  ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"
  if [ -z "$ADMIN_PASSWORD" ]; then
    ADMIN_PASSWORD="$(tr -dc 'A-Za-z2-9' </dev/urandom | head -c 16)"
    GENERATED_PASSWORD=1
  fi

  # Facility settings for a children's centre. The 'nonformal' preset leaves
  # self-signup and self-delete switched on, which is wrong where accounts are
  # issued by staff -- the same rule the website follows.
  SETTINGS_FILE="$(mktemp)"
  cat > "$SETTINGS_FILE" <<'JSON'
{
  "learner_can_login_with_no_password": true,
  "learner_can_sign_up": false,
  "learner_can_edit_username": false,
  "learner_can_edit_password": false,
  "learner_can_edit_name": false,
  "learner_can_delete_account": false,
  "show_download_button_in_learn": false
}
JSON

  sudo -u "$KOLIBRI_USER" env KOLIBRI_HOME="$KOLIBRI_HOME" \
    "$KOLIBRI_VENV/bin/kolibri" manage provisiondevice \
      --facility "$FACILITY_NAME" \
      --superusername "$ADMIN_USERNAME" \
      --superuserpassword "$ADMIN_PASSWORD" \
      --preset nonformal \
      --language_id en \
      --facility_settings "$SETTINGS_FILE" \
      --noinput >/dev/null
  rm -f "$SETTINGS_FILE"

  note "facility '$FACILITY_NAME' created"
  note "super admin: $ADMIN_USERNAME"
  if [ "${GENERATED_PASSWORD:-0}" = "1" ]; then
    printf '\n    \033[1mSuper admin password: %s\033[0m\n' "$ADMIN_PASSWORD"
    note "Write this down now. It is not stored anywhere."
  fi
  note "Learners sign in with their username alone -- no password, by design,"
  note "for 8-14 year olds on the centre's own network."
fi

# ------------------------------------------------------------ kolibri unit --
say "Installing the kolibri service"
install -m 0644 "$SCRIPT_DIR/kolibri.service" /etc/systemd/system/kolibri.service
# Placeholders are __NAME__ rather than @NAME@: systemd reads a leading @ in
# ExecStart as an argv[0] override, which would silently break the unit.
sed -i "s|__KOLIBRI_USER__|$KOLIBRI_USER|g; s|__KOLIBRI_HOME__|$KOLIBRI_HOME|g; s|__KOLIBRI_VENV__|$KOLIBRI_VENV|g" \
  /etc/systemd/system/kolibri.service

# --------------------------------------------------------------- sync tool --
say "Installing the reporting tool"
install -m 0755 "$SCRIPT_DIR/kolibri-sync.py" /usr/local/bin/kolibri-sync.py

if [ -f /etc/kolibri-sync.env ]; then
  note "/etc/kolibri-sync.env already exists; leaving it alone"
else
  cat > /etc/kolibri-sync.env <<ENV
# Reporting from this centre to the Mill Creek-AR website.
#
# SITE_SYNC_URL and KOLIBRI_SYNC_SECRET are only needed for automatic
# reporting over the internet. Leave them blank to work entirely offline:
# write the file to a USB stick instead and upload it in the teacher portal.
#
#   kolibri-sync.py --days 30 --out /media/usb/kolibri-sync.json

SITE_SYNC_URL=
KOLIBRI_SYNC_SECRET=
KOLIBRI_HOME=$KOLIBRI_HOME
KOLIBRI_BIN=$KOLIBRI_VENV/bin/kolibri
CENTRE_DEVICE=$(hostname | cut -c1-60)
CENTRE_FACILITY=$FACILITY_NAME
ENV
  chmod 600 /etc/kolibri-sync.env
  note "wrote /etc/kolibri-sync.env (readable only by root: it holds a shared secret)"
fi

install -m 0644 "$SCRIPT_DIR/kolibri-sync.service" /etc/systemd/system/kolibri-sync.service
install -m 0644 "$SCRIPT_DIR/kolibri-sync.timer"   /etc/systemd/system/kolibri-sync.timer

systemctl daemon-reload
systemctl enable --now kolibri.service
note "kolibri service enabled"

# ------------------------------------------------------------------- done ---
IP_ADDRESS="$(hostname -I 2>/dev/null | awk '{print $1}')"
say "Done"
cat <<DONE

    Learners and teachers reach Kolibri at:

        http://${IP_ADDRESS:-<this machine's address>}:$HTTP_PORT

    Next, in order:

    1.  Check it is running:        systemctl status kolibri
    2.  Add learning content:       see docs/KOLIBRI_CENTRE_SETUP.md, section 4.
                                    Channels are imported from a USB stick when
                                    the centre has no line.
    3.  Add the learners:           download the roster from the website's
                                    teacher portal (Centre -> Download roster),
                                    copy it here, then run
                                      sudo -u $KOLIBRI_USER env KOLIBRI_HOME=$KOLIBRI_HOME \\
                                        $KOLIBRI_VENV/bin/kolibri manage bulkimportusers roster.csv
    4.  Report activity back:       kolibri-sync.py --days 30 --out /media/usb/kolibri-sync.json
                                    and upload that file in the teacher portal.

    For automatic reporting when the centre has internet, put the website's
    address and shared secret in /etc/kolibri-sync.env, then:

        systemctl enable --now kolibri-sync.timer

DONE
