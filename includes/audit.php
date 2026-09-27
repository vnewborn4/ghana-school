<?php
/**
 * The audit trail.
 *
 * Kept apart from roles.php on purpose: learner pages need audit() but must
 * never pull in auth.php, which opens the sponsor session the moment it is
 * included. Two sessions cannot be open at once, so an accidental include
 * would silently route a learner's login into the wrong session.
 */
require_once __DIR__ . '/db.php';

/**
 * Record an action on a child's record. Append-only: nothing in this
 * application updates or deletes audit rows.
 *
 * IP addresses are hashed, never stored raw.
 */
function audit(string $action, array $opts = []): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = db()->prepare(
        'INSERT INTO audit_log
            (actor_user_id, actor_learner_id, action, subject_type, subject_id, detail, ip_hash)
         VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $opts['actor_user_id']    ?? null,
        $opts['actor_learner_id'] ?? null,
        $action,
        $opts['subject_type'] ?? '',
        $opts['subject_id']   ?? null,
        isset($opts['detail']) ? mb_substr((string)$opts['detail'], 0, 500) : null,
        $ip !== '' ? hash('sha256', $ip) : null,
    ]);
}
