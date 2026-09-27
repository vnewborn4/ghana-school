<?php
/**
 * Learner sessions.
 *
 * Children authenticate against the `learners` table with a staff-issued
 * username and PIN -- never an email address, and never through the sponsor
 * login. The two run on separate session cookies, so a learner session can
 * never be mistaken for a sponsor or staff session and vice versa.
 *
 * PINs are low entropy by design (a child has to remember one on a shared
 * lab machine), so attempt limiting is the whole defence. A locked account is
 * unlocked in person by a teacher, which is also the right identity check.
 */
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';

const LEARNER_SESSION   = 'MCACADEMY';
const LEARNER_IDLE_MAX  = 1800;   // 30 minutes -- shared lab machines
const PIN_MAX_ATTEMPTS  = 6;
const PIN_LOCK_MINUTES  = 15;

function learner_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === LEARNER_SESSION) return;
        // Some other include opened the sponsor session first. Close it rather
        // than silently writing learner state into the wrong session.
        session_write_close();
    }
    session_name(LEARNER_SESSION);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'path'     => '/',   // the draft preview under /students/ needs to read it
    ]);
    session_start();

    // Idle timeout. Children share computers; a forgotten session is a real risk.
    if (!empty($_SESSION['learner_id'])) {
        $last = (int)($_SESSION['last_seen'] ?? 0);
        if ($last > 0 && (time() - $last) > LEARNER_IDLE_MAX) {
            logout_learner();
            learner_session_start();
            $_SESSION['timed_out'] = true;
            return;
        }
        $_SESSION['last_seen'] = time();
    }
}

function learner_csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verify_learner_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
        http_response_code(400);
        exit('Your session expired. Please go back and sign in again.');
    }
}

function current_learner_id(): ?int {
    return !empty($_SESSION['learner_id']) ? (int)$_SESSION['learner_id'] : null;
}

/** The signed-in learner's row, or null. */
function current_learner(): ?array {
    static $cached = null;
    $id = current_learner_id();
    if ($id === null) return null;
    if ($cached !== null && (int)$cached['id'] === $id) return $cached;

    $stmt = db()->prepare('SELECT * FROM learners WHERE id=? AND active=1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { logout_learner(); return null; }
    return $cached = $row;
}

/** Require a signed-in learner, sending them to the academy sign-in if not. */
function require_learner(): array {
    $learner = current_learner();
    if (!$learner) {
        header('Location: ' . app_url('academy/login.php'));
        exit;
    }
    set_lang($learner['preferred_lang']);
    // A learner with a first-time PIN must choose their own before doing anything else.
    if ((int)$learner['must_change_pin'] === 1
        && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'change_pin.php') {
        header('Location: ' . app_url('academy/change_pin.php'));
        exit;
    }
    return $learner;
}

function login_learner(int $learnerId): void {
    session_regenerate_id(true);
    $_SESSION['learner_id'] = $learnerId;
    $_SESSION['last_seen']  = time();
}

function logout_learner(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Check a username and PIN.
 * Returns the learner row, or null with $reason set to a translation key.
 */
function attempt_learner_login(string $username, string $pin, ?string &$reason = null): ?array {
    $username = strtolower(trim($username));
    $stmt = db()->prepare('SELECT * FROM learners WHERE username=?');
    $stmt->execute([$username]);
    $learner = $stmt->fetch();

    // Always spend the same work whether or not the account exists.
    $hash = $learner['pin_hash'] ?? '$2y$10$usesomesillystringforsalt000000000000000000000000000000';
    $ok   = password_verify($pin, $hash);

    if (!$learner) { $reason = 'login.error'; return null; }

    if ((int)$learner['active'] !== 1) { $reason = 'login.inactive'; return null; }

    if (!empty($learner['locked_until']) && strtotime($learner['locked_until']) > time()) {
        $reason = 'login.locked';
        return null;
    }

    if (!$ok) {
        $attempts = (int)$learner['failed_attempts'] + 1;
        if ($attempts >= PIN_MAX_ATTEMPTS) {
            $upd = db()->prepare(
                'UPDATE learners SET failed_attempts=?, locked_until=DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id=?'
            );
            $upd->execute([$attempts, PIN_LOCK_MINUTES, $learner['id']]);
            $reason = 'login.locked';
        } else {
            $upd = db()->prepare('UPDATE learners SET failed_attempts=? WHERE id=?');
            $upd->execute([$attempts, $learner['id']]);
            $reason = 'login.error';
        }
        return null;
    }

    $upd = db()->prepare('UPDATE learners SET failed_attempts=0, locked_until=NULL, last_login_at=NOW() WHERE id=?');
    $upd->execute([$learner['id']]);
    return $learner;
}

/**
 * Award a badge if the learner does not already hold it. Safe to call often.
 */
function award_badge(int $learnerId, string $badgeSlug): void {
    $stmt = db()->prepare(
        'INSERT IGNORE INTO learner_badges (learner_id, badge_id, awarded_on)
         SELECT ?, id, NOW() FROM badges WHERE slug=?'
    );
    $stmt->execute([$learnerId, $badgeSlug]);
}
