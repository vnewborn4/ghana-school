<?php
/**
 * Shared chrome for the student academy and the teacher portal.
 *
 * Expects, before inclusion:
 *   $portalKind  'student' or 'staff'
 *   $pageTitle   page title
 *   $navItems    [['href'=>..., 'label'=>..., 'key'=>...], ...]
 *   $portalName  heading shown beside the brand
 *   $signOut     ['action'=>url, 'csrf'=>token]  (optional)
 *   $csrfToken   the session's CSRF token, for the offline queue to reuse
 *   $offlineFor  the signed-in learner's username, on student pages only.
 *                Everything the browser stores offline is tagged with it, so
 *                queued work can never be sent under another child's name.
 *   $siteSlug    the learner's page slug, when there is an editor on the page
 */
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/i18n.php';
add_security_headers();

$portalKind = $portalKind ?? 'student';
$pageTitle  = $pageTitle  ?? 'Academy';
$portalName = $portalName ?? 'Academy';
$navItems   = $navItems   ?? [];
$offlineFor = $offlineFor ?? '';
$siteSlug   = $siteSlug   ?? '';
$csrfToken  = $csrfToken  ?? ($signOut['csrf'] ?? '');
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

// These pages are personal to one person and these machines are shared, so
// they must not sit in the browser's cache for whoever opens it next. The
// service worker never stores page HTML either; only the shell.
if (!headers_sent()) {
    header('Cache-Control: no-store, must-revalidate');
    header('Pragma: no-cache');
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars(current_lang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#133d34">
    <?php if ($csrfToken !== ''): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <?php endif; ?>
    <title><?= htmlspecialchars($pageTitle) ?> | Mill Creek-AR Academy</title>
    <link rel="icon" type="image/svg+xml" href="<?= app_url('assets/images/brand-mark.svg') ?>">
    <?php if ($portalKind === 'student'): ?>
    <link rel="manifest" href="<?= app_url('academy/manifest.webmanifest') ?>">
    <link rel="apple-touch-icon" href="<?= app_url('assets/images/academy-icon-192.png') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/academy.css') ?>">
</head>
<body class="academy academy-<?= htmlspecialchars($portalKind) ?>"
      data-base-path="<?= htmlspecialchars(BASE_PATH) ?>"
      <?php if ($offlineFor !== ''): ?>data-learner="<?= htmlspecialchars($offlineFor) ?>"<?php endif; ?>
      <?php if ($siteSlug !== ''): ?>data-site-slug="<?= htmlspecialchars($siteSlug) ?>"<?php endif; ?>>
<a class="skip-link" href="#academy-main">Skip to main content</a>

<header class="academy-bar">
    <div class="academy-bar-inner">
        <a class="academy-brand" href="<?= app_url($portalKind === 'staff' ? 'teach/index.php' : 'academy/index.php') ?>">
            <img src="<?= app_url('assets/images/brand-mark.svg') ?>" alt="" width="38" height="38">
            <span><strong>Mill Creek-AR</strong><small><?= htmlspecialchars($portalName) ?></small></span>
        </a>

        <?php if ($navItems): ?>
        <nav class="academy-nav" aria-label="<?= htmlspecialchars($portalName) ?> navigation">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= htmlspecialchars($item['href']) ?>"
                   class="academy-nav-link<?= ($item['key'] ?? '') === $currentPage ? ' active' : '' ?>"
                   <?= ($item['key'] ?? '') === $currentPage ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <div class="academy-bar-actions">
            <?php if ($portalKind === 'student' && count(supported_langs()) > 1): ?>
            <div class="lang-switch" role="group" aria-label="<?= e('common.language') ?>">
                <?php foreach (supported_langs() as $code => $label): ?>
                    <a href="<?= htmlspecialchars(lang_switch_url($code)) ?>"
                       class="<?= $code === current_lang() ? 'active' : '' ?>"
                       lang="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($signOut)): ?>
            <form method="post" action="<?= htmlspecialchars($signOut['action']) ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($signOut['csrf']) ?>">
                <button class="button-signout" type="submit"><?= e('common.sign_out') ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="academy-main" class="academy-main">
