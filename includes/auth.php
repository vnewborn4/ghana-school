<?php
if (!defined('BASE_PATH')) {
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    define('BASE_PATH', ($scriptDir === '/' || $scriptDir === '.') ? '' : rtrim($scriptDir, '/'));
}

function app_url($path = '') {
    $clean = ltrim($path, '/');
    return ($clean === '') ? (BASE_PATH ? BASE_PATH : '/') : (BASE_PATH . '/' . $clean);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $cookiePath = BASE_PATH !== '' ? BASE_PATH . '/' : '/';
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array('httponly'=>true,'samesite'=>'Lax','secure'=>$isSecure,'path'=>$cookiePath));
    } else {
        session_set_cookie_params(0, $cookiePath, '', $isSecure, true);
    }
    session_start();
}
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            $_SESSION['csrf'] = md5(uniqid(mt_rand(), true));
        }
    }
    return $_SESSION['csrf'];
}
function verify_csrf() {
    $csrf = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : '';
    $postCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if (!isset($_POST['csrf']) || !hash_equals($csrf, $postCsrf)) {
        http_response_code(400); exit('Your session expired. Please go back and try again.');
    }
}
function is_logged_in() { return !empty($_SESSION['user_id']); }
function login_user($userId) { session_regenerate_id(true); $_SESSION['user_id'] = (int)$userId; }
function logout_user() { $_SESSION = array(); if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); }
function require_user() { if (!is_logged_in()) { header('Location: login.php?return=portal.php'); exit; } return (int)$_SESSION['user_id']; }
function require_admin() {
    $id=require_user(); require_once __DIR__.'/db.php'; $stmt=db()->prepare('SELECT is_admin FROM users WHERE id=?'); $stmt->execute(array($id));
    $val = $stmt->fetchColumn();
    if((int)($val ? $val : 0)!==1){ http_response_code(403); exit('Administrator access is required.'); }
    return $id;
}
function safe_return_path($fallback='portal.php') { 
    $path = isset($_POST['return']) ? $_POST['return'] : (isset($_GET['return']) ? $_GET['return'] : ''); 
    return in_array($path, array('portal.php','adopt.php','admin.php','profile_studio.php'), true) ? $path : $fallback; 
}

function current_user() {
    static $user = null;
    if ($user !== null) return $user;
    if (!is_logged_in()) return null;
    require_once __DIR__.'/db.php';
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, is_admin, is_teacher FROM users WHERE id=?');
    $stmt->execute(array((int)$_SESSION['user_id']));
    $row = $stmt->fetch();
    $user = $row ? $row : null;
    return $user;
}

function is_admin() {
    $u = current_user();
    return $u && (int)$u['is_admin'] === 1;
}

function is_teacher() {
    $u = current_user();
    if (!$u) return false;
    $teacher = isset($u['is_teacher']) ? (int)$u['is_teacher'] : 0;
    return ((int)$u['is_admin'] === 1 || $teacher === 1);
}

function require_teacher() {
    $id = require_user();
    if (!is_teacher()) {
        http_response_code(403);
        exit('Teacher or administrator access is required.');
    }
    return $id;
}
