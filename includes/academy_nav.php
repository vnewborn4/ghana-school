<?php
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/i18n.php';

function student_nav(): array {
    return [
        ['href' => app_url('academy/index.php'),  'label' => t('dash.my_lessons'), 'key' => 'index.php'],
        ['href' => app_url('academy/mysite.php'), 'label' => t('dash.my_page'),    'key' => 'mysite.php'],
        ['href' => app_url('academy/badges.php'), 'label' => t('dash.my_badges'),  'key' => 'badges.php'],
    ];
}

function staff_nav(bool $isAdmin): array {
    $items = [
        ['href' => app_url('teach/index.php'),   'label' => 'Roster',      'key' => 'index.php'],
        ['href' => app_url('teach/onboard.php'), 'label' => 'Onboard',     'key' => 'onboard.php'],
        ['href' => app_url('teach/marking.php'), 'label' => 'Marking',     'key' => 'marking.php'],
        ['href' => app_url('teach/pages.php'),   'label' => 'Student pages','key' => 'pages.php'],
        ['href' => app_url('teach/report.php'),  'label' => 'Report',       'key' => 'report.php'],
    ];
    if ($isAdmin) {
        $items[] = ['href' => app_url('admin.php'), 'label' => 'Sponsor admin', 'key' => 'admin.php'];
    }
    return $items;
}
