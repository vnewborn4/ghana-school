<?php
/**
 * Dual-language support: English plus Ghanaian languages.
 *
 * Translations live in lang/<code>.php as flat key => string arrays. Any key
 * missing from a translation falls back to English, so a partly translated
 * language is always safe to ship.
 *
 * Programming keywords, HTML tags and CSS properties are deliberately NOT
 * translated -- every piece of documentation a learner will meet later uses
 * the English terms.
 */

/** Languages offered in the switcher, in display order. */
function supported_langs(): array {
    return [
        'en'  => 'English',
        // Twi is partly translated; untranslated keys fall back to English.
        // See the review note at the top of lang/tw.php.
        'tw'  => 'Twi',
        // Ready to enable once lang/<code>.php is written and reviewed locally:
        // 'gaa' => 'Ga',
        // 'ee'  => 'Eʋegbe',
        // 'ha'  => 'Hausa',
    ];
}

/**
 * Resolve the active language from ?lang=, then the session, then the cookie.
 * An explicit ?lang= is remembered for next time.
 */
function current_lang(): string {
    static $lang = null;
    if ($lang !== null) return $lang;

    $supported = supported_langs();
    $requested = $_GET['lang'] ?? null;
    if (is_string($requested) && isset($supported[$requested])) {
        $lang = $requested;
        if (session_status() === PHP_SESSION_ACTIVE) $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, [
            'expires'  => time() + 31536000,
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        return $lang;
    }

    foreach ([$_SESSION['lang'] ?? null, $_COOKIE['lang'] ?? null] as $candidate) {
        if (is_string($candidate) && isset($supported[$candidate])) {
            return $lang = $candidate;
        }
    }
    return $lang = 'en';
}

/** Force a language for this request (used to honour a learner's preference). */
function set_lang(string $code): void {
    if (isset(supported_langs()[$code]) && session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['lang'] = $code;
    }
}

/**
 * Translate a key. Placeholders are written {like_this} and passed in $vars.
 * Returns the key itself if it is missing everywhere, which makes gaps obvious
 * during review rather than silently blank.
 */
function t(string $key, array $vars = []): string {
    static $strings = null, $fallback = null;
    if ($fallback === null) {
        $fallback = require __DIR__ . '/../lang/en.php';
        $file = __DIR__ . '/../lang/' . current_lang() . '.php';
        $strings = ($file !== __DIR__ . '/../lang/en.php' && is_file($file))
            ? require $file
            : $fallback;
    }
    $text = $strings[$key] ?? $fallback[$key] ?? $key;
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string)$value, $text);
    }
    return $text;
}

/** Translate and escape for HTML output. Use this in templates. */
function e(string $key, array $vars = []): string {
    return htmlspecialchars(t($key, $vars), ENT_QUOTES, 'UTF-8');
}

/** Current URL with the language swapped, for the switcher links. */
function lang_switch_url(string $code): string {
    $query = $_GET;
    $query['lang'] = $code;
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return $path . '?' . http_build_query($query);
}
