# lab/ — the coding activities

Self-hosted, offline-capable coding tools for ages 8–14. Everything here is
static: no server code runs, nothing reads a cookie, nothing touches the
database. `lab/.htaccess` relaxes the site's Content-Security-Policy for this
directory only, because block languages compile to JavaScript and `eval` it.

The directory ships empty. Add the tools you want, then publish the matching
modules in the teacher portal.

## Blockly Games (start here)

Apache-2.0. About 3.8 MB compressed, 6.7 MB unzipped — small enough to work on
a metered connection, and it runs entirely offline once loaded.

1. Download the offline build for your language from
   <https://github.com/google/blockly-games/wiki/offline>.
2. Unzip it so the games sit at `lab/blockly-games/` — you should end up with
   `lab/blockly-games/index.html`, `maze.html`, `turtle.html` and so on.
3. In the teacher portal, publish the **Blockly: Maze** and
   **Blockly: Turtle drawing** modules. They ship unpublished so learners
   never meet a broken link.

The offline build fixes the interface language at download time, so download
the English build now and add a second language directory later if you want
one (`lab/blockly-games-tw/`, with its own module row).

## Snap! (for the older learners)

AGPL-3.0, roughly 5 MB, pure static JavaScript — Scratch-like blocks with real
computer science underneath (functions, recursion). Copy the release into
`lab/snap/` and add a module pointing at `lab/snap/snap.html`.

## Scratch / TurboWarp

Highest recognition with children and the biggest project library, but tens of
megabytes. Do not serve it from the public site over Accra mobile data — run
**TurboWarp Desktop** on the lab machines, or serve it from the centre's local
server, and have learners submit their `.sb3` project file to the assignment.

## What not to put here

- Anything that needs PHP, Node, Python or a database. This directory is
  static by design, and `.htaccess` disables script execution.
- Anything that signs a child in to an outside service. Self-hosting is what
  keeps learners out of global comment sections and moderation queues.

## Checking it worked

After adding a tool, open its page while signed in as a learner and confirm
the activity actually runs. If blocks appear but nothing executes, the CSP is
not being applied — check that `AllowOverride` permits `.htaccess` on this
host, and see `docs/ACADEMY_INTEGRATION.md`.
