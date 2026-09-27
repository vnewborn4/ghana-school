# The learning centre's Kolibri server

How to set up and run the offline learning server at the Mill Creek-AR
Learning Center in Accra, and how it exchanges files with this website.

**The point of this machine:** lessons keep working when the internet does
not. Kolibri serves a full library over the centre's own Wi-Fi from a box in
the corner of the room. Children use it exactly the same whether the line is
up, down, or was never connected. Nothing here depends on a connection except
the optional automatic reporting in section 7, and there is an offline route
for that too.

Everything below was checked against **Kolibri 0.19.5**.

---

## 1. What you need

| | |
| --- | --- |
| **The machine** | A Raspberry Pi 4 or 5 with 4 GB of RAM, or any spare laptop from about 2015 onwards. A Pi 3 works for a handful of learners at once but will feel slow. |
| **Storage** | 64 GB minimum, 128 GB comfortable. Content channels are large: a full Khan Academy import runs to tens of gigabytes. On a Pi, use a good quality card or, much better, a USB SSD. |
| **Power** | A UPS or battery bank. This matters more than the specification: Kolibri keeps its data in SQLite, and losing power mid-write is the one thing that can genuinely corrupt it. |
| **Network** | A Wi-Fi router. It does **not** need an internet connection. The router's only job is to let the learners' devices and the server see each other. |
| **Learner devices** | Anything with a browser: the lab PCs, a phone, a donated tablet. |

**Give the server a fixed address** on the router (a DHCP reservation is
easiest). If its address moves, every bookmark in the room breaks.

---

## 2. Install

Copy the `tools/kolibri/` directory onto the machine, then:

```
sudo ./install-kolibri.sh
```

It is safe to run again; every step checks whether it has already been done.

The script:

- installs Kolibri into `/opt/kolibri` and its data into `/var/lib/kolibri`;
- installs **fake-hwclock**, because a Raspberry Pi has no battery-backed
  clock and an offline Pi can otherwise reboot believing it is 1970 — which
  would put the wrong date on every activity record;
- writes `options.ini`: serves on port 8080 on every interface, serves HTML5
  content apps on a separate port so they run in their own origin, and turns
  off the statistics pingback that would otherwise fail on every boot;
- provisions the facility **hardened for children** (section 3);
- installs a `kolibri` systemd service that starts on boot and is given 120
  seconds to shut down cleanly, so a power cut during a restart does not
  interrupt a database write;
- installs the reporting tool and its daily timer.

It prints the super admin password **once**. Write it down.

Check it came up:

```
systemctl status kolibri
```

Learners then reach it at `http://<the server's address>:8080`. Write that
address on the wall.

---

## 3. Why the facility settings are changed

Kolibri's `nonformal` preset leaves **learner self-signup** and **self-delete**
switched on. That is wrong for a centre where a child's account is issued by
staff against a signed guardian consent form — the same rule the website
follows. The install script sets:

| Setting | Value | Why |
| --- | --- | --- |
| `learner_can_login_with_no_password` | on | An 8-year-old on a trusted local network should sign in with the username on their welcome card and nothing else. One credential, not two. |
| `learner_can_sign_up` | off | Accounts are issued by staff. Always. |
| `learner_can_edit_username` | off | The username is the link back to their account on the website. |
| `learner_can_edit_password` | off | There is no password to edit. |
| `learner_can_delete_account` | off | A child cannot delete their own record. |
| `show_download_button_in_learn` | off | Keeps shared lab machines tidy. |

To change any of these later, use Kolibri's own Facility settings page as the
super admin.

---

## 4. Add the learning content

Content comes in **channels**. Import them once; they then work forever with
no connection.

### If the centre has a connection, even briefly

In Kolibri, sign in as the super admin and go to **Device → Channels →
Import → Kolibri Studio**. Import while the line is up; the content stays.

### If it does not — the usual case

Do the download somewhere with good bandwidth, onto a USB drive, and carry it.

On a machine that has internet and Kolibri installed:

```
kolibri manage importchannel network <channel-id>
kolibri manage importcontent network <channel-id>
kolibri manage exportcontent /media/usb        # writes the channel to the stick
```

At the centre:

```
sudo -u kolibri env KOLIBRI_HOME=/var/lib/kolibri \
  /opt/kolibri/bin/kolibri manage importchannel disk <channel-id> /media/usb
sudo -u kolibri env KOLIBRI_HOME=/var/lib/kolibri \
  /opt/kolibri/bin/kolibri manage importcontent disk <channel-id> /media/usb
```

### What to import first, for 8–14 year olds

| Channel | Why |
| --- | --- |
| **Blockly Games** | The coding ladder this programme is built around — and having it inside Kolibri means progress is tracked, which the static copy in the website's `lab/` cannot do. Start here. |
| **Khan Academy** | Maths and computing fundamentals. Large; import selected topics rather than everything. |
| **African Storybook** / **Global Digital Library** | Reading, with stories that are not all set somewhere else. Small, and worth the space. |
| **PhET** | Science simulations, for the 12–14 end. |
| **CK-12** | STEM for the oldest learners. |
| **TESSA** | Teacher resources for sub-Saharan Africa. For the coaches, not the children. |

Import a little at a time and watch the disk. Kolibri stops importing at
250 MB free by default, which protects the database but leaves you with a
half-imported channel.

**Review a channel before the children see it.** Curating what is on the
shelf is a normal part of running a centre, and it is much easier here than
on the open web — which is a large part of why the content lives on this box.

---

## 5. Add the learners

The website is the roster of record. Take it from there so a child has the
same username in both places.

1. In the teacher portal, open **Centre**, choose a class, and
   **Download roster for Kolibri**.
2. Copy the file to the server.
3. Import it:

```
sudo -u kolibri env KOLIBRI_HOME=/var/lib/kolibri \
  /opt/kolibri/bin/kolibri manage bulkimportusers roster.csv
```

Things worth knowing, all learned the hard way:

- **Kolibri usernames cannot contain hyphens.** The academy's `ama-k9`
  becomes `ama_k9` here. The website does the swap on the way out and back,
  so you never have to think about it — but that is why the two look
  slightly different.
- **Re-running the file is safe.** Learners already at the centre are
  reported as *"Username is duplicated"* and skipped. Nothing is changed,
  nothing is reset.
- **Never add `--delete`.** It removes every user missing from the file. With
  a single class roster, that means everyone else at the centre.
- The starting password in the file is random and unused, because learners
  sign in with their username alone. If you ever turn passwords on, set them
  in Kolibri's admin — not by re-importing.

Then, in Kolibri, make a teacher a **coach** for the class so they can see
progress and assign lessons.

---

## 6. A normal day

Nothing to do. The server starts with the power and serves the room.

- Learners go to `http://<address>:8080` and sign in with their username.
- Coaches use Kolibri's own **Coach** section for lessons, quizzes and
  per-learner progress. That is the right place for day-to-day teaching; the
  website is for the roster, assignments and reporting outward.

---

## 7. Report activity back to the website

The website needs centre activity for the programme report and the public
figures. Two routes, same file, same result.

### Offline — the normal route

```
kolibri-sync.py --days 30 --out /media/usb/kolibri-sync.json
```

Carry the stick, and upload the file in the teacher portal under **Centre →
Upload activity**. Uploading the same window twice is safe: rows are keyed on
the day and the learner, so nothing is double counted.

### Automatic — when the centre has a connection

Put the website address and a shared secret in `/etc/kolibri-sync.env`:

```
SITE_SYNC_URL=https://millcreek-ar-learning.com/api/kolibri_sync.php
KOLIBRI_SYNC_SECRET=<a long random string>
```

Set the same secret on the web server as `KOLIBRI_SYNC_SECRET`, then:

```
sudo systemctl enable --now kolibri-sync.timer
```

It runs each evening after the centre closes. A run that fails because there
is no line is expected and harmless — the next one sends the same window
again. The endpoint refuses anything without a valid signature, and refuses
everything if the secret is not configured, rather than accepting unsigned data.

**Generate the secret** with something like:

```
head -c 32 /dev/urandom | base64
```

### What is actually sent

A date, a Kolibri username, and three numbers: sessions, activities
completed, minutes. That is all. No content titles, no real names, nothing a
child typed. You can read the file yourself before uploading it — it is plain
JSON and a month is only a few kilobytes.

---

## 8. Backups

The centre's records live on one machine in one room. Back them up.

```
sudo -u kolibri env KOLIBRI_HOME=/var/lib/kolibri \
  /opt/kolibri/bin/kolibri manage dbbackup
```

This writes a dated copy into `/var/lib/kolibri/backups`. Do it **weekly**,
and copy the file onto a USB stick that lives somewhere else. A backup on the
same SD card as the original is not a backup.

To restore: `kolibri manage dbrestore --latest`, or name a file.

Content channels do not need backing up — they can be imported again. The
database is the irreplaceable part: it holds every learner and everything
they have done.

---

## 9. When something is wrong

**Nobody can reach it.**
`systemctl status kolibri`. If it is running, check the address with
`hostname -I` — a router reboot may have moved it. Fix that with a DHCP
reservation.

**It is very slow.**
Usually the SD card. Move to a USB SSD. Also check free space:
`df -h /var/lib/kolibri`.

**The dates are wrong on everything.**
The clock. A Pi with no internet and no battery clock guesses. Check with
`date`; fix with `sudo date -s "2026-09-27 14:30"` and let fake-hwclock keep
it. The sync tool refuses to run if the clock reads before 2025, because
wrong dates are worse than no report.

**It did not shut down cleanly and now will not start.**
`kolibri manage dbrestore --latest`. This is what the backups are for. Then
buy the UPS.

**The sync is refused with "Bad signature".**
The secrets do not match. Compare `/etc/kolibri-sync.env` on the server with
`KOLIBRI_SYNC_SECRET` on the web host — a trailing newline or a stray quote is
usually the culprit.

**A learner's activity shows at the centre but not against their account on
the website.**
Their Kolibri username does not match. It should be the academy username with
underscores for hyphens. The teacher portal shows the expected Kolibri
username on the learner's page.

---

## 10. What this deliberately does not do

- **It does not sync learner accounts automatically.** Roster out, activity
  back, both as files a person moves. Two systems quietly writing to each
  other's user records is how children get locked out of both.
- **It does not send the website anything a child wrote.** Only counts.
- **It does not use Kolibri's Data Portal.** That is a fine service, but it
  sends data to a third party; the centre's own records going to the centre's
  own website is a shorter and clearer path.
- **It is not a replacement for the website's academy.** Kolibri is the
  library and the day-to-day classroom. The website is the roster, the
  assignments, the students' own web pages, and the reporting outward. Each
  does what it is good at.
