<?php
/**
 * Staff roles and the audit trail.
 *
 * Sponsors and staff share the users table because both are adults with
 * email accounts. Children never appear there -- they live in `learners`.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

/**
 * Require the signed-in user to hold one of the given roles.
 * Admins are implicitly allowed wherever teachers are.
 * Returns the user row.
 */
function require_role(string ...$allowed): array {
    $userId = require_user();
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, role, is_admin FROM users WHERE id=?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) { logout_user(); header('Location: ' . app_url('login.php')); exit; }

    $role = $user['role'] ?? 'sponsor';
    if ((int)($user['is_admin'] ?? 0) === 1) $role = 'admin';   // legacy flag still wins
    $user['role'] = $role;

    if ($role === 'admin') return $user;                        // admin implies teacher
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('You do not have access to this area.');
    }
    return $user;
}

function is_admin_user(array $user): bool {
    return ($user['role'] ?? '') === 'admin';
}
