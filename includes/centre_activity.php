<?php
/**
 * Learning-centre activity synced from Kolibri.
 *
 * Kolibri runs on a small server at the centre in Accra, on the local network.
 * It needs no internet to teach: children use it over the centre's own Wi-Fi
 * whether or not the line is up. This file receives what that server reports
 * back, by either of two routes:
 *
 *   HTTP   the centre has a connection, and tools/kolibri/kolibri-sync.py
 *          posts a signed payload to api/kolibri_sync.php
 *   upload staff carry the same file on a USB stick and upload it in the
 *          teacher portal
 *
 * Both routes end here, so the offline path is not a lesser one. A window can
 * be re-sent any number of times: rows are keyed on (date, username).
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

const CENTRE_PAYLOAD_MAX_BYTES = 2097152;   // 2 MB is a generous term of rows
const CENTRE_MAX_ROWS          = 5000;

/**
 * A Kolibri username maps to an academy slug by swapping underscores for
 * hyphens. Kolibri rejects hyphens in usernames; the academy uses them
 * because the slug is also a public URL. The transform is the whole mapping.
 */
function centre_username_to_slug(string $kolibriUsername): string {
    return str_replace('_', '-', strtolower(trim($kolibriUsername)));
}

function centre_slug_to_username(string $slug): string {
    return str_replace('-', '_', strtolower(trim($slug)));
}

/**
 * Validate a decoded payload. Returns an error string, or null if it is sound.
 * Everything here arrives from outside the application, so nothing is assumed.
 */
function centre_validate_payload(mixed $payload): ?string {
    if (!is_array($payload))                      return 'The payload is not an object.';
    if (($payload['version'] ?? null) !== 1)      return 'Unsupported payload version.';
    if (!isset($payload['rows']) || !is_array($payload['rows'])) return 'The payload has no rows.';
    if (count($payload['rows']) > CENTRE_MAX_ROWS) {
        return 'That payload has too many rows (' . count($payload['rows']) . '). Sync a shorter window.';
    }
    return null;
}

/**
 * Record a payload. Returns ['received'=>int,'matched'=>int,'sync_id'=>int].
 * $method is 'http' or 'upload'; $actorUserId is set for an upload.
 */
function centre_ingest(array $payload, string $method, ?int $actorUserId = null): array {
    $pdo = db();
    $device   = mb_substr((string)($payload['device'] ?? ''), 0, 60);
    $facility = mb_substr((string)($payload['facility'] ?? ''), 0, 120);
    $from = centre_valid_date((string)($payload['window']['from'] ?? ''));
    $to   = centre_valid_date((string)($payload['window']['to'] ?? ''));

    $upsert = $pdo->prepare(
        'INSERT INTO centre_activity
            (activity_date, kolibri_username, learner_id, sessions, completed, minutes, device)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
            learner_id = VALUES(learner_id),
            sessions   = VALUES(sessions),
            completed  = VALUES(completed),
            minutes    = VALUES(minutes),
            device     = VALUES(device),
            received_at = CURRENT_TIMESTAMP'
    );
    $findLearner = $pdo->prepare('SELECT id FROM learners WHERE username = ?');

    $received = 0; $matched = 0;
    $learnerCache = [];

    $pdo->beginTransaction();
    try {
        foreach ($payload['rows'] as $row) {
            if (!is_array($row)) continue;
            $date = centre_valid_date((string)($row['date'] ?? ''));
            $username = mb_substr(strtolower(trim((string)($row['username'] ?? ''))), 0, 60);
            if ($date === null || $username === '' || !preg_match('/^[a-z0-9_]+$/', $username)) {
                continue;   // a malformed row is skipped, never fatal
            }

            $slug = centre_username_to_slug($username);
            if (!array_key_exists($slug, $learnerCache)) {
                $findLearner->execute([$slug]);
                $found = $findLearner->fetchColumn();
                $learnerCache[$slug] = $found !== false ? (int)$found : null;
            }
            $learnerId = $learnerCache[$slug];
            if ($learnerId !== null) $matched++;

            $upsert->execute([
                $date,
                $username,
                $learnerId,
                min(65535, max(0, (int)($row['sessions']  ?? 0))),
                min(65535, max(0, (int)($row['completed'] ?? 0))),
                min(65535, max(0, (int)($row['minutes']   ?? 0))),
                $device,
            ]);
            $received++;
        }

        $log = $pdo->prepare(
            'INSERT INTO centre_syncs
                (device, facility, window_from, window_to, rows_received, rows_matched, method, uploaded_by)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $log->execute([$device, $facility, $from, $to, $received, $matched, $method, $actorUserId]);
        $syncId = (int)$pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    audit('centre.sync', [
        'actor_user_id' => $actorUserId,
        'subject_type'  => 'centre_sync',
        'subject_id'    => $syncId,
        'detail'        => 'method=' . $method . ' device=' . $device
                         . ' rows=' . $received . ' matched=' . $matched,
    ]);

    return ['received' => $received, 'matched' => $matched, 'sync_id' => $syncId];
}

function centre_valid_date(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return ($d && $d->format('Y-m-d') === $value) ? $value : null;
}

/** Day-by-day totals over a range, derived so a re-sync cannot double count. */
function centre_daily_totals(string $from, string $to): array {
    $stmt = db()->prepare(
        'SELECT activity_date,
                COUNT(*) AS learners_active,
                SUM(sessions)  AS sessions,
                SUM(completed) AS completed,
                SUM(minutes)   AS minutes
         FROM centre_activity
         WHERE activity_date BETWEEN ? AND ?
         GROUP BY activity_date
         ORDER BY activity_date DESC'
    );
    $stmt->execute([$from, $to]);
    return $stmt->fetchAll();
}

/** Totals over a range, for the programme report. */
function centre_totals(string $from, string $to): array {
    $stmt = db()->prepare(
        'SELECT COUNT(DISTINCT kolibri_username) AS learners,
                COUNT(DISTINCT activity_date)    AS days_open,
                COALESCE(SUM(sessions), 0)       AS sessions,
                COALESCE(SUM(completed), 0)      AS completed,
                COALESCE(SUM(minutes), 0)        AS minutes
         FROM centre_activity WHERE activity_date BETWEEN ? AND ?'
    );
    $stmt->execute([$from, $to]);
    return $stmt->fetch() ?: ['learners'=>0,'days_open'=>0,'sessions'=>0,'completed'=>0,'minutes'=>0];
}

/** One learner's centre activity, for their page in the teacher portal. */
function centre_learner_totals(int $learnerId): array {
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(sessions),0) AS sessions,
                COALESCE(SUM(completed),0) AS completed,
                COALESCE(SUM(minutes),0) AS minutes,
                COUNT(DISTINCT activity_date) AS days,
                MAX(activity_date) AS last_seen
         FROM centre_activity WHERE learner_id = ?'
    );
    $stmt->execute([$learnerId]);
    return $stmt->fetch() ?: ['sessions'=>0,'completed'=>0,'minutes'=>0,'days'=>0,'last_seen'=>null];
}

function centre_recent_syncs(int $limit = 10): array {
    $limit = max(1, min(50, $limit));
    return db()->query(
        'SELECT s.*, u.first_name, u.last_name
         FROM centre_syncs s LEFT JOIN users u ON u.id = s.uploaded_by
         ORDER BY s.created_at DESC LIMIT ' . $limit
    )->fetchAll();
}

/**
 * Kolibri's bulk user import format, generated from the academy roster, so a
 * learner onboarded on the website gets the same username at the centre.
 *
 * Deliberately narrow: a first name or nickname, never a surname, and no
 * birth year or gender. Kolibri asks for those; the centre does not need to
 * give them.
 *
 * This adds learners who are not yet in Kolibri. `kolibri manage
 * bulkimportusers` matches existing people by the UUID column, which the
 * website does not hold, so a learner already at the centre is reported as
 * "Username is duplicated" and skipped. That is the safe outcome -- nothing
 * is changed or reset -- and it means the file can be re-run without care.
 *
 * The starting password is random and inert: the install script turns on
 * Kolibri's no-password sign-in for learners, so a child signs in with the
 * username on their welcome card and nothing else. If passwords are ever
 * turned on, set them in Kolibri's own admin rather than here -- two
 * credentials for one child is how children end up locked out.
 */
function centre_kolibri_roster_csv(?int $cohortId = null): string {
    $sql = 'SELECT l.username, l.display_name, c.name AS cohort_name
            FROM learners l LEFT JOIN cohorts c ON c.id = l.cohort_id
            WHERE l.active = 1';
    $params = [];
    if ($cohortId) { $sql .= ' AND l.cohort_id = ?'; $params[] = $cohortId; }
    $sql .= ' ORDER BY c.name, l.display_name';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $out = fopen('php://temp', 'r+');
    fwrite($out, "\xEF\xBB\xBF");   // Kolibri writes a BOM; match it
    fputcsv($out, [
        'Database ID (UUID)', 'Username (USERNAME)', 'Password (PASSWORD)',
        'Full name (FULL_NAME)', 'User type (USER_TYPE)', 'Identifier (IDENTIFIER)',
        'Birth year (BIRTH_YEAR)', 'Gender (GENDER)',
        'Learner enrollment (ENROLLED_IN)', 'Coach assignment (ASSIGNED_TO)',
    ]);
    foreach ($stmt->fetchAll() as $learner) {
        fputcsv($out, [
            '',                                               // Kolibri assigns the UUID
            centre_slug_to_username($learner['username']),
            centre_starting_password(),
            $learner['display_name'],
            'LEARNER',
            '', '', '',
            $learner['cohort_name'] ?? '',
            '',
        ]);
    }
    rewind($out);
    return (string)stream_get_contents($out);
}

/**
 * A random starting password for a new Kolibri learner. It is never shown,
 * because learners sign in with their username alone; it exists only because
 * bulkimportusers requires the column to be filled for a new user.
 */
function centre_starting_password(): string {
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < 12; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}
