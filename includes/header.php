<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';
add_security_headers();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Mill Creek-AR Learning Center';
$pageDescription = isset($pageDescription) ? $pageDescription : 'Technology education and mentorship for young people in Accra, Ghana.';
$pageImage = isset($pageImage) ? (strpos($pageImage, 'http') === 0 ? $pageImage : app_url($pageImage)) : app_url('assets/images/mill-creek-crest.jpeg');
$currentPage = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'index.php');
$canonicalUrl = 'https://millcreek-ar-learning.com/' . ($currentPage === 'index.php' ? '' : $currentPage);
$pageImageAbs = strpos($pageImage, 'http') === 0 ? $pageImage : 'https://millcreek-ar-learning.com/' . ltrim($pageImage, '/');
if (!function_exists('nav_active')) {
    function nav_active($page, $currentPage) { return $page === $currentPage ? ' aria-current="page" class="nav-link active"' : ' class="nav-link"'; }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Mill Creek-AR Learning Center">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?> | Mill Creek-AR Learning Center">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageImageAbs) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?> | Mill Creek-AR Learning Center">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($pageImageAbs) ?>">
    <meta name="theme-color" content="#133d34">
    <title><?= htmlspecialchars($pageTitle) ?> | Mill Creek-AR Learning Center</title>
    <link rel="icon" type="image/svg+xml" href="<?= app_url('assets/images/brand-mark.svg') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    <script defer src="<?= app_url('assets/js/site.js') ?>"></script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "NGO",
      "name": "Mill Creek-AR Learning Center",
      "url": "https://millcreek-ar-learning.com/",
      "logo": "https://millcreek-ar-learning.com/assets/images/mill-creek-crest.jpeg",
      "description": "Technology education, Python coding instruction, and mentorship for young people in Accra, Ghana.",
      "funder": {
        "@type": "Organization",
        "name": "AD2 Alumni Foundation",
        "taxID": "47-1468217"
      },
      "sameAs": [
        "https://www.zeffy.com/en-US/donation-form/donate-to-change-lives-through-it"
      ]
    }
    </script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="announcement">A learning initiative in Accra, Ghana, in conjunction with the AD2 Alumni Foundation</div>
    <div class="nav-wrap">
        <a class="brand" href="<?= app_url('index.php') ?>" aria-label="Mill Creek-AR Learning Center home">
            <img class="brand-crest" src="<?= app_url('assets/images/mill-creek-crest.jpeg') ?>" alt="Mill Creek-AR Crest" width="44" height="44">
            <span><strong>Mill Creek-AR</strong><small>Learning Center · Accra</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><span></span><span></span><span></span><span class="sr-only">Open menu</span></button>
        <nav id="site-nav" class="site-nav" aria-label="Main navigation">
            <a<?= nav_active('index.php', $currentPage) ?> href="<?= app_url('index.php') ?>">Home</a>
            <a<?= nav_active('about.php', $currentPage) ?> href="<?= app_url('about.php') ?>">Our story</a>
            <a<?= nav_active('impact.php', $currentPage) ?> href="<?= app_url('impact.php') ?>">Why technology</a>
            <a<?= nav_active('bio.php', $currentPage) ?> href="<?= app_url('bio.php') ?>">Founder</a>
            <a<?= nav_active('foundation.php', $currentPage) ?> href="<?= app_url('foundation.php') ?>">Partnership</a>
            <?php if(is_logged_in()): ?>
                <a<?= nav_active('portal.php', $currentPage) ?> href="<?= app_url('portal.php') ?>">Dashboard</a>
                <?php if(is_admin()): ?>
                    <a<?= nav_active('admin.php', $currentPage) ?> href="<?= app_url('admin.php') ?>">Admin</a>
                <?php endif; ?>
                <?php if(is_teacher()): ?>
                    <a<?= nav_active('profile_studio.php', $currentPage) ?> href="<?= app_url('profile_studio.php') ?>">Lab Studio</a>
                <?php endif; ?>
            <?php else: ?>
                <a<?= nav_active('login.php', $currentPage) ?> href="<?= app_url('login.php') ?>">Sign in</a>
            <?php endif; ?>
            <a<?= nav_active('adopt.php', $currentPage) ?> href="<?= app_url('adopt.php') ?>">Give or sponsor</a>
        </nav>
    </div>
</header>
<main id="main-content">
