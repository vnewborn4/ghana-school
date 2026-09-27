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
 */
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/i18n.php';
add_security_headers();

$portalKind = $portalKind ?? 'student';
$pageTitle  = $pageTitle  ?? 'Academy';
$portalName = $portalName ?? 'Academy';
$navItems   = $navItems   ?? [];
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="<?= htmlspecialchars(current_lang()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> | Mill Creek-AR Academy</title>
    <link rel="icon" type="image/svg+xml" href="<?= app_url('assets/images/brand-mark.svg') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/academy.css') ?>">
</head>
<body class="academy academy-<?= htmlspecialchars($portalKind) ?>">
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
