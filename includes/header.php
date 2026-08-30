<?php
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
    <link rel="stylesheet" href="/ghana-school/assets/css/style.css">
    <script defer src="/ghana-school/assets/js/site.js"></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="announcement">A learning initiative in Accra, Ghana, in conjunction with the AD2 Alumni Foundation</div>
    <div class="nav-wrap">
        <a class="brand" href="/ghana-school/index.php" aria-label="Mill Creek-AR Learning Center home">
            <span class="brand-mark" aria-hidden="true">MC</span>
            <span><strong>Mill Creek-AR</strong><small>Learning Center · Accra</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><span></span><span></span><span></span><span class="sr-only">Open menu</span></button>
        <nav id="site-nav" class="site-nav" aria-label="Main navigation">
            <a<?= nav_active('index.php', $currentPage) ?> href="/ghana-school/index.php">Home</a>
            <a<?= nav_active('about.php', $currentPage) ?> href="/ghana-school/about.php">Our story</a>
            <a<?= nav_active('impact.php', $currentPage) ?> href="/ghana-school/impact.php">Why technology</a>
            <a<?= nav_active('bio.php', $currentPage) ?> href="/ghana-school/bio.php">Founder</a>
            <a<?= nav_active('adopt.php', $currentPage) ?> href="/ghana-school/adopt.php">Sponsor a journey</a>
        </nav>
    </div>
</header>
<main id="main-content">
