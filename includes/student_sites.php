<?php
/**
 * Student web spaces -- the files behind /students/<slug>/.
 *
 * Files are kept OUTSIDE the document root and streamed by students/serve.php,
 * so nothing a learner writes is ever executed by the server. Learners edit
 * `draft`; `live` is a copy taken when a teacher approves publication, so a
 * learner can never change what is already public.
 *
 * Set GHANA_STORAGE_PATH to a directory above the web root wherever the host
 * allows it. The default sits inside the project and is protected by
 * storage/.htaccess, which is a fallback, not the preferred arrangement.
 */
require_once __DIR__ . '/db.php';

const SITE_MAX_BYTES      = 5242880;   // 5 MB per learner
const SITE_MAX_FILE_BYTES = 1048576;   // 1 MB per file
const SITE_MAX_FILES      = 100;
const SITE_KEEP_REVISIONS = 20;

/** Extensions a learner may create. Anything executable is absent by design. */
function site_allowed_extensions(): array {
    return ['html','htm','css','js','txt','md','json','png','jpg','jpeg','gif','webp'];
}

function site_storage_root(): string {
    $root = getenv('GHANA_STORAGE_PATH') ?: (dirname(__DIR__) . '/storage');
    return rtrim(str_replace('\\', '/', $root), '/');
}

function site_is_valid_slug(string $slug): bool {
    return (bool)preg_match('/^[a-z0-9][a-z0-9-]{1,38}[a-z0-9]$/', $slug);
}

/** Absolute path to a learner's draft or live directory. */
function site_dir(string $slug, string $which = 'draft'): ?string {
    if (!site_is_valid_slug($slug) || !in_array($which, ['draft','live','revisions'], true)) {
        return null;
    }
    return site_storage_root() . '/student_sites/' . $slug . '/' . $which;
}

function site_ensure_dirs(string $slug): bool {
    foreach (['draft','live','revisions'] as $which) {
        $dir = site_dir($slug, $which);
        if ($dir === null) return false;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) return false;
    }
    return true;
}

/**
 * A learner's filename, made safe. Returns null if the name or extension is
 * not allowed. Directories are not permitted -- a learner site is flat.
 */
function site_safe_filename(string $name): ?string {
    $name = basename(str_replace('\\', '/', $name));
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9._-]+/', '-', $name);
    $name = trim($name, '-.');
    if ($name === '' || strlen($name) > 60 || str_contains($name, '..')) return null;

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, site_allowed_extensions(), true)) return null;
    return $name;
}

function site_content_type(string $file): string {
    return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
        'html', 'htm' => 'text/html; charset=utf-8',
        'css'         => 'text/css; charset=utf-8',
        'js'          => 'text/javascript; charset=utf-8',
        'json'        => 'application/json; charset=utf-8',
        'txt', 'md'   => 'text/plain; charset=utf-8',
        'png'         => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'         => 'image/gif',
        'webp'        => 'image/webp',
        default       => 'application/octet-stream',
    };
}

/** Every file in one of a learner's directories, with its size. */
function site_files(string $slug, string $which = 'draft'): array {
    $dir = site_dir($slug, $which);
    if ($dir === null || !is_dir($dir)) return [];
    $out = [];
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . '/' . $entry;
        if (is_file($path)) $out[$entry] = filesize($path);
    }
    ksort($out);
    return $out;
}

function site_bytes_used(string $slug, string $which = 'draft'): int {
    return array_sum(site_files($slug, $which));
}

function site_read(string $slug, string $which, string $file): ?string {
    $safe = site_safe_filename($file);
    $dir  = site_dir($slug, $which);
    if ($safe === null || $dir === null) return null;
    $path = $dir . '/' . $safe;
    return is_file($path) ? (file_get_contents($path) ?: '') : null;
}

/**
 * Write one of a learner's files. Enforces the extension whitelist, the
 * per-file cap, the file count, and the total quota. Returns an error
 * message, or null on success.
 */
function site_write(string $slug, string $file, string $contents): ?string {
    $safe = site_safe_filename($file);
    if ($safe === null) return 'That kind of file is not allowed.';
    if (!site_ensure_dirs($slug)) return 'Could not open your page folder.';
    if (strlen($contents) > SITE_MAX_FILE_BYTES) return 'That file is too big.';

    $existing = site_files($slug, 'draft');
    if (!isset($existing[$safe]) && count($existing) >= SITE_MAX_FILES) {
        return 'You have too many files. Ask your teacher to help you tidy up.';
    }
    $projected = site_bytes_used($slug, 'draft') - ($existing[$safe] ?? 0) + strlen($contents);
    if ($projected > SITE_MAX_BYTES) {
        return 'Your page folder is full. Ask your teacher to help you tidy up.';
    }

    $path = site_dir($slug, 'draft') . '/' . $safe;
    if (file_put_contents($path, $contents, LOCK_EX) === false) return 'Could not save that file.';

    site_refresh_usage($slug);
    return null;
}

function site_delete_file(string $slug, string $file): bool {
    $safe = site_safe_filename($file);
    $dir  = site_dir($slug, 'draft');
    if ($safe === null || $dir === null || $safe === 'index.html') return false;
    $path = $dir . '/' . $safe;
    $ok = is_file($path) && unlink($path);
    if ($ok) site_refresh_usage($slug);
    return $ok;
}

/**
 * Store an uploaded image. Images are re-encoded rather than copied, which
 * strips EXIF metadata -- including the GPS coordinates a phone camera adds.
 * That is a safeguarding requirement, not an optimisation.
 */
function site_store_image(string $slug, array $upload, ?string &$storedName = null): ?string {
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return 'No file was uploaded.';
    if ($upload['size'] > SITE_MAX_FILE_BYTES) return 'That picture is too big (1 MB is the limit).';
    if (!is_uploaded_file($upload['tmp_name'])) return 'That upload was not accepted.';

    $info = @getimagesize($upload['tmp_name']);
    if ($info === false) return 'That file is not a picture.';

    $source = match ($info[2]) {
        IMAGETYPE_PNG  => @imagecreatefrompng($upload['tmp_name']),
        IMAGETYPE_JPEG => @imagecreatefromjpeg($upload['tmp_name']),
        IMAGETYPE_GIF  => @imagecreatefromgif($upload['tmp_name']),
        IMAGETYPE_WEBP => @imagecreatefromwebp($upload['tmp_name']),
        default        => false,
    };
    if (!$source) return 'That kind of picture is not supported. Use PNG, JPG, GIF or WEBP.';

    // Cap the long edge so one photo cannot eat a learner's whole quota.
    $maxEdge = 1200;
    $w = imagesx($source); $h = imagesy($source);
    if ($w > $maxEdge || $h > $maxEdge) {
        $scale = $maxEdge / max($w, $h);
        $resized = imagescale($source, (int)round($w * $scale), (int)round($h * $scale));
        if ($resized !== false) { imagedestroy($source); $source = $resized; }
    }

    $base = site_safe_filename(pathinfo($upload['name'] ?? 'picture', PATHINFO_FILENAME) . '.png');
    $name = $base ?? 'picture.png';
    if (!site_ensure_dirs($slug)) { imagedestroy($source); return 'Could not open your page folder.'; }

    $existing = site_files($slug, 'draft');
    if (!isset($existing[$name]) && count($existing) >= SITE_MAX_FILES) {
        imagedestroy($source);
        return 'You have too many files. Ask your teacher to help you tidy up.';
    }

    ob_start();
    imagepng($source, null, 6);
    $encoded = (string)ob_get_clean();
    imagedestroy($source);

    $projected = site_bytes_used($slug, 'draft') - ($existing[$name] ?? 0) + strlen($encoded);
    if ($projected > SITE_MAX_BYTES) return 'Your page folder is full.';

    if (file_put_contents(site_dir($slug, 'draft') . '/' . $name, $encoded, LOCK_EX) === false) {
        return 'Could not save that picture.';
    }
    $storedName = $name;
    site_refresh_usage($slug);
    return null;
}

function site_refresh_usage(string $slug): void {
    $files = site_files($slug, 'draft');
    $stmt = db()->prepare(
        'UPDATE student_sites SET bytes_used=?, file_count=?, last_edited_at=NOW() WHERE slug=?'
    );
    $stmt->execute([array_sum($files), count($files), $slug]);
}

/** Zip the current live directory into revisions/, keeping the most recent few. */
function site_snapshot(string $slug): void {
    $live = site_dir($slug, 'live');
    $revs = site_dir($slug, 'revisions');
    if ($live === null || $revs === null || !is_dir($live) || !class_exists('ZipArchive')) return;
    $files = site_files($slug, 'live');
    if (!$files) return;

    $zip = new ZipArchive();
    $name = $revs . '/' . date('Y-m-d_His') . '.zip';
    if ($zip->open($name, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return;
    foreach (array_keys($files) as $file) $zip->addFile($live . '/' . $file, $file);
    $zip->close();

    $kept = glob($revs . '/*.zip') ?: [];
    if (count($kept) > SITE_KEEP_REVISIONS) {
        sort($kept);
        foreach (array_slice($kept, 0, count($kept) - SITE_KEEP_REVISIONS) as $old) @unlink($old);
    }
}

/** Copy draft over live. Called only from the teacher/admin approval path. */
function site_publish_draft(string $slug): bool {
    $draft = site_dir($slug, 'draft');
    $live  = site_dir($slug, 'live');
    if ($draft === null || $live === null || !is_dir($draft)) return false;

    site_snapshot($slug);

    foreach (array_keys(site_files($slug, 'live')) as $file) @unlink($live . '/' . $file);
    foreach (array_keys(site_files($slug, 'draft')) as $file) {
        if (!copy($draft . '/' . $file, $live . '/' . $file)) return false;
    }
    return true;
}

/** Remove every file a learner owns. Used for data erasure requests. */
function site_erase(string $slug): void {
    $base = site_storage_root() . '/student_sites/' . $slug;
    if (!site_is_valid_slug($slug) || !is_dir($base)) return;
    foreach (['draft','live','revisions'] as $which) {
        $dir = site_dir($slug, $which);
        foreach (glob($dir . '/*') ?: [] as $file) @unlink($file);
        @rmdir($dir);
    }
    @rmdir($base);
}

/**
 * Advisory pre-publication scan. This flags things for a teacher to look at.
 * It is never a gate -- a person always reads the page before it goes online.
 */
function site_prepublish_warnings(string $slug): array {
    $warnings = [];
    $text = '';
    foreach (array_keys(site_files($slug, 'draft')) as $file) {
        if (preg_match('/\.(html?|css|js|txt|md)$/i', $file)) {
            $text .= "\n" . (site_read($slug, 'draft', $file) ?? '');
        }
    }
    if (trim($text) === '') return ['This page has no content yet.'];

    $checks = [
        ['/[\w.+-]+@[\w-]+\.[\w.]{2,}/',                 'Looks like an email address.'],
        ['/(?:\+233|0)\s?\d{2}\s?\d{3}\s?\d{4}/',        'Looks like a phone number.'],
        ['/\b\d{9,}\b/',                                  'Looks like a long number — check it is not a phone number.'],
        ['/<script[^>]+src\s*=\s*["\']?https?:/i',        'Loads a script from another website.'],
        ['/<iframe/i',                                    'Contains an iframe.'],
        ['/<form[^>]+action\s*=\s*["\']?https?:/i',       'Contains a form that sends data to another website.'],
        ['/\b(facebook|instagram|tiktok|snapchat|whatsapp|telegram)\b/i', 'Mentions a social media service.'],
        ['/\b(?:house\s?number|p\.?o\.?\s?box|street|avenue)\b/i', 'Might contain a home address.'],
    ];
    foreach ($checks as [$pattern, $message]) {
        if (preg_match($pattern, $text)) $warnings[] = $message;
    }
    return $warnings;
}

/** The starter page a learner receives at onboarding. */
function site_seed(string $slug, string $displayName): void {
    if (!site_ensure_dirs($slug)) return;
    $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeName}'s page</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <h1>Hello, I am {$safeName}</h1>

  <p>This is my page. I am learning to build websites at the
     Mill Creek-AR Learning Center in Accra.</p>

  <p>Change these words to your own. Remember: never put your full name,
     your address, your phone number, or your school on this page.</p>

  <script src="script.js"></script>
</body>
</html>
HTML;

    $css = <<<CSS
/* Change the colours and see what happens. */

body {
  background: #f6f0e4;
  color: #17231f;
  font-family: system-ui, sans-serif;
  line-height: 1.6;
  margin: 0 auto;
  max-width: 40rem;
  padding: 2rem 1rem;
}

h1 {
  color: #133d34;
}
CSS;

    $js = <<<'JS'
// Your code goes here. Try changing the message, then press Save.

console.log('Hello from my page!');
JS;

    foreach (['index.html' => $html, 'style.css' => $css, 'script.js' => $js] as $file => $body) {
        $path = site_dir($slug, 'draft') . '/' . $file;
        if (!file_exists($path)) file_put_contents($path, $body, LOCK_EX);
    }
    site_refresh_usage($slug);
}

/* ------------------------------------------------------------------ *
 * Assignment uploads
 *
 * Kept in the same private storage tree as student sites and served only
 * through a session-checked script. Project files (.sb3, .xml, .zip) are
 * stored as-is and never unpacked on the server -- an .sb3 is a ZIP, and
 * unpacking untrusted archives server-side is how you get owned.
 * ------------------------------------------------------------------ */

const SUBMISSION_MAX_BYTES = 4194304;   // 4 MB

function submission_allowed_extensions(): array {
    return ['png','jpg','jpeg','gif','webp','sb3','xml','zip','txt','pdf'];
}

function submission_storage_dir(int $learnerId): string {
    return site_storage_root() . '/submissions/' . $learnerId;
}

/**
 * Store an uploaded assignment file under a generated name. Returns the
 * stored relative path, or null with $error set.
 */
function submission_store_upload(int $learnerId, array $upload, ?string &$error = null): ?string {
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { $error = null; return null; }
    if (($upload['error'] ?? 1) !== UPLOAD_ERR_OK) { $error = 'asg.file_too_big'; return null; }
    if (!is_uploaded_file($upload['tmp_name'])) { $error = 'asg.file_type'; return null; }
    if ($upload['size'] > SUBMISSION_MAX_BYTES) { $error = 'asg.file_too_big'; return null; }

    $ext = strtolower(pathinfo((string)($upload['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, submission_allowed_extensions(), true)) { $error = 'asg.file_type'; return null; }

    $dir = submission_storage_dir($learnerId);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) { $error = 'common.problem'; return null; }

    // Never reuse the learner's own filename.
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($upload['tmp_name'], $dir . '/' . $name)) { $error = 'common.problem'; return null; }

    return $learnerId . '/' . $name;
}

/** Absolute path for a stored submission file, or null if it escapes storage. */
function submission_absolute_path(string $relative): ?string {
    if (!preg_match('#^\d+/[A-Za-z0-9._-]+$#', $relative)) return null;
    $path = site_storage_root() . '/submissions/' . $relative;
    return is_file($path) ? $path : null;
}
