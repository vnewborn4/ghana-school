<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/assets.php';
add_security_headers();

/**
 * Cache policy.
 *
 * Pages default to no-store, because most of the ones using this header show
 * something personal -- a sponsor's dashboard, an administrator's workspace,
 * a form carrying a token -- and these are often shared computers.
 *
 * A page with nothing personal on it opts in by setting `$publicPage = true;`
 * before including this file. Those pages are revalidated on every visit but
 * their body is only sent when it has actually changed, which on a metered
 * connection is most of the saving with none of the risk of serving one
 * person's page to another.
 *
 * Note the navigation differs by whether someone is signed in, so these are
 * `private`: a shared proxy must never keep a copy.
 */
if (!headers_sent()) {
    if (!empty($publicPage)) {
        header('Cache-Control: private, no-cache, must-revalidate');
        header_remove('Pragma');
        header_remove('Expires');

        // Hash the finished page and answer with 304 when the visitor already
        // has it. Headers set inside the callback are still in time, because
        // nothing has been flushed while the buffer is open.
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            ob_start(function (string $html): string {
                $etag = '"' . substr(sha1($html), 0, 27) . '"';
                header('ETag: ' . $etag);

                $sent = (string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '');
                foreach (explode(',', $sent) as $candidate) {
                    $candidate = trim($candidate);
                    if (str_starts_with($candidate, 'W/')) $candidate = substr($candidate, 2);
                    // mod_deflate appends -gzip to the tag it hands the
                    // browser, which comes back on the next request. Without
                    // this the comparison would never match and every page
                    // would be sent in full -- the bug this exists to fix.
                    $candidate = (string)preg_replace('/-(?:gzip|br)"$/', '"', $candidate);
                    if ($candidate !== '' && hash_equals($etag, $candidate)) {
                        http_response_code(304);
                        return '';
                    }
                }
                return $html;
            });
        }
    } else {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
}

$pageTitle = $pageTitle ?? 'Mill Creek-AR Learning Center';
$pageDescription = $pageDescription ?? 'Technology education and mentorship for young people in Accra, Ghana.';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
function nav_active(string $page, string $currentPage): string { return $page === $currentPage ? ' aria-current="page" class="nav-link active"' : ' class="nav-link"'; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="theme-color" content="#133d34">
    <title><?= htmlspecialchars($pageTitle) ?> | Mill Creek-AR Learning Center</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset_url('assets/images/brand-mark.svg') ?>">
    <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
    <script defer src="<?= asset_url('assets/js/site.js') ?>"></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="announcement">A learning initiative in Accra, Ghana, in conjunction with the AD2 Alumni Foundation</div>
    <div class="nav-wrap">
        <a class="brand" href="<?= app_url('index.php') ?>" aria-label="Mill Creek-AR Learning Center home">
            <img class="brand-mark" src="<?= asset_url('assets/images/brand-mark.svg') ?>" alt="" width="46" height="46">
            <span><strong>Mill Creek-AR</strong><small>Learning Center · Accra</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><span></span><span></span><span></span><span class="sr-only">Open menu</span></button>
        <nav id="site-nav" class="site-nav" aria-label="Main navigation">
            <a<?= nav_active('index.php', $currentPage) ?> href="<?= app_url('index.php') ?>">Home</a>
            <a<?= nav_active('about.php', $currentPage) ?> href="<?= app_url('about.php') ?>">Our story</a>
            <a<?= nav_active('impact.php', $currentPage) ?> href="<?= app_url('impact.php') ?>">Why technology</a>
            <a<?= nav_active('bio.php', $currentPage) ?> href="<?= app_url('bio.php') ?>">Founder</a>
            <a<?= nav_active('foundation.php', $currentPage) ?> href="<?= app_url('foundation.php') ?>">Partnership</a>
            <?php if(is_logged_in()): ?><a<?= nav_active('portal.php', $currentPage) ?> href="<?= app_url('portal.php') ?>">My dashboard</a><?php else: ?><a<?= nav_active('login.php', $currentPage) ?> href="<?= app_url('login.php') ?>">Sign in</a><?php endif; ?>
            <a<?= nav_active('adopt.php', $currentPage) ?> href="<?= app_url('adopt.php') ?>">Sponsor a journey</a>
        </nav>
    </div>
</header>
<main id="main-content">
