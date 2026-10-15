<?php
function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') !== false) {
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v, " \t\n\r\0\x0B\"'");
                    if (getenv($k) === false) {
                        putenv("{$k}={$v}");
                        $_ENV[$k] = $v;
                    }
                }
            }
        }
    }

    $host = getenv('GHANA_DB_HOST') ?: 'mysql.us.cloudlogin.co';
    $name = getenv('GHANA_DB_NAME') ?: 'bigdog95_millcreek';
    $user = getenv('GHANA_DB_USER') ?: 'bigdog95_millcreek';
    $password = getenv('GHANA_DB_PASSWORD') ?: '';
    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
