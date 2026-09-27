<?php
/**
 * BASE_PATH for pages that live one directory below the site root
 * (academy/, teach/, students/). Include this BEFORE auth.php, which would
 * otherwise read the subdirectory as the site root and break every asset URL.
 */
if (!defined('BASE_PATH')) {
    $scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
    define('BASE_PATH', ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/'));
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        $clean = ltrim($path, '/');
        return ($clean === '') ? (BASE_PATH ?: '/') : (BASE_PATH . '/' . $clean);
    }
}
