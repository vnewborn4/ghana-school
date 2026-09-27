<?php
/**
 * Serves student web pages from private storage.
 *
 * Nothing under /students/ is a real directory: files live outside the
 * document root and are streamed from here, so the server never executes
 * anything a learner wrote, whatever they manage to save.
 *
 * Student pages are sent with `Content-Security-Policy: sandbox`, which puts
 * the response in an opaque origin. Scripts on a learner's page therefore
 * cannot read cookies or same-origin data belonging to the sponsor portal,
 * even though it is served from the same hostname. Moving these pages to a
 * separate subdomain would be stronger still -- see docs/ACADEMY_INTEGRATION.md.
 *
 *   /students/<slug>/[file]          published page  (public)
 *   /students/preview/<slug>/[file]  working draft   (learner or staff only)
 */

$defaultSessionName = session_name();   // capture before any session is opened

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/student_sites.php';

$slug    = (string)($_GET['s'] ?? '');
$file    = (string)($_GET['f'] ?? 'index.html');
$version = ($_GET['v'] ?? 'live') === 'draft' ? 'draft' : 'live';

if ($file === '' || str_ends_with($file, '/')) $file = 'index.html';

function deny(int $code, string $message): never {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><meta charset="utf-8"><title>Not available</title>'
       . '<p style="font-family:system-ui;padding:2rem">' . htmlspecialchars($message) . '</p>';
    exit;
}

if (!site_is_valid_slug($slug)) deny(404, 'That page was not found.');

$stmt = db()->prepare(
    'SELECT ss.*, l.active AS learner_active
     FROM student_sites ss JOIN learners l ON l.id = ss.learner_id
     WHERE ss.slug = ?'
);
$stmt->execute([$slug]);
$site = $stmt->fetch();
if (!$site) deny(404, 'That page was not found.');

/* ---- who is asking? ------------------------------------------------- *
 * The learner and staff sessions use different cookies, and PHP can only
 * hold one open at a time, so each is probed in turn and closed again.   */
$isOwner = false;
$isStaff = false;

if ($version === 'draft' || $site['status'] !== 'published') {
    require_once __DIR__ . '/../includes/learner_auth.php';

    if (!empty($_COOKIE[LEARNER_SESSION])) {
        learner_session_start();
        $isOwner = (current_learner_id() === (int)$site['learner_id']);
        session_write_close();
    }

    if (!$isOwner && !empty($_COOKIE[$defaultSessionName])) {
        session_name($defaultSessionName);
        require_once __DIR__ . '/../includes/auth.php';
        if (is_logged_in()) {
            $check = db()->prepare('SELECT role, is_admin FROM users WHERE id=?');
            $check->execute([(int)$_SESSION['user_id']]);
            $user = $check->fetch();
            $isStaff = $user && (in_array($user['role'] ?? '', ['teacher','admin'], true)
                                 || (int)($user['is_admin'] ?? 0) === 1);
        }
        session_write_close();
    }
}

$published = $site['status'] === 'published' && (int)$site['learner_active'] === 1;

if ($version === 'draft') {
    if (!$isOwner && !$isStaff) deny(403, 'This page is not published yet.');
} elseif (!$published && !$isStaff) {
    deny(404, $site['status'] === 'suspended'
        ? 'This page is not available.'
        : 'This page has not been published yet.');
}

$safeFile = site_safe_filename($file);
if ($safeFile === null) deny(404, 'That file was not found.');

$dir = site_dir($slug, $version);
if ($dir === null) deny(404, 'That file was not found.');

$path = $dir . '/' . $safeFile;
$real = realpath($path);
$base = realpath($dir);
if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
    deny(404, 'That file was not found.');
}

/* ---- headers -------------------------------------------------------- *
 * sandbox without allow-same-origin gives the response an opaque origin.
 * allow-modals is included so a learner's first alert() works, which for
 * this age group is most of the fun. Forms, popups, downloads and top-level
 * navigation are all withheld.                                            */
header('Content-Type: ' . site_content_type($safeFile));
header('Content-Length: ' . (string)filesize($real));
header('Content-Security-Policy: sandbox allow-scripts allow-modals');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Cache-Control: ' . ($version === 'draft' ? 'no-store' : 'public, max-age=300'));

readfile($real);
