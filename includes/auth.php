<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']),'path'=>'/ghana-school/']);
    session_start();
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
        http_response_code(400); exit('Your session expired. Please go back and try again.');
    }
}
function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function login_user(int $userId): void { session_regenerate_id(true); $_SESSION['user_id'] = $userId; }
function logout_user(): void { $_SESSION = []; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); }
function require_user(): int { if (!is_logged_in()) { header('Location: login.php?return=portal.php'); exit; } return (int)$_SESSION['user_id']; }
function safe_return_path(string $fallback='portal.php'): string { $path=$_POST['return'] ?? $_GET['return'] ?? ''; return in_array($path,['portal.php','adopt.php'],true) ? $path : $fallback; }
