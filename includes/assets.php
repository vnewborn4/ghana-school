<?php
/**
 * Versioned URLs for static files.
 *
 * Stylesheets, scripts and images are served with a long cache lifetime, so a
 * returning visitor downloads none of them again. That only works if a
 * changed file gets a new URL, which is what the ?v= stamp is for: it is the
 * file's modification time, so uploading a new copy invalidates it at once
 * and nothing else does.
 *
 * This matters more here than on most sites. A homepage visit is about 105 KB,
 * and 95 KB of that is files that never change between visits. On metered
 * mobile data in Accra, paying for them once instead of every time is the
 * single biggest saving available.
 *
 * Requires app_url(), so include it after auth.php or paths.php.
 */
if (!function_exists('asset_url')) {
    /**
     * @param string $path Path from the project root, e.g. 'assets/css/style.css'
     */
    function asset_url(string $path): string {
        static $stamps = [];

        $clean = ltrim($path, '/');
        if (!array_key_exists($clean, $stamps)) {
            $file = dirname(__DIR__) . '/' . $clean;
            // A missing file falls back to an unversioned URL rather than
            // failing: a broken image is better than a broken page.
            $stamps[$clean] = is_file($file) ? (string)filemtime($file) : '';
        }

        $url = app_url($clean);
        return $stamps[$clean] === '' ? $url : $url . '?v=' . $stamps[$clean];
    }
}
