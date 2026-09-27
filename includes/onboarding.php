<?php
/**
 * Automated learner onboarding.
 *
 * One form submission creates the account, the PIN, the web space, and the
 * seeded starter page, and writes the audit entry. The PIN is returned to
 * the caller once so it can be printed on a welcome card, and is never
 * recoverable afterwards -- a forgotten PIN is reset in person by a teacher,
 * which is also the right identity check for a child.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/roles.php';
require_once __DIR__ . '/student_sites.php';

/**
 * Turn a display name into a URL-safe, non-identifying slug.
 * The result appears in a public address forever, so it carries a first name
 * or nickname and a short random marker -- never a surname or a birthdate.
 */
function onboarding_make_slug(string $displayName): string {
    $base = strtolower(trim($displayName));
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $base);
        if ($converted !== false) $base = $converted;
    }
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim((string)$base, '-');
    if ($base === '' || strlen($base) < 2) $base = 'learner';
    $base = substr($base, 0, 24);

    $letters = 'abcdefghjkmnpqrstuvwxyz';   // no i/l/o -- they read as 1 and 0
    for ($attempt = 0; $attempt < 40; $attempt++) {
        $suffix = $letters[random_int(0, strlen($letters) - 1)] . random_int(1, 9);
        $slug = $base . '-' . $suffix;
        $stmt = db()->prepare(
            'SELECT (SELECT COUNT(*) FROM learners WHERE username=?)
                  + (SELECT COUNT(*) FROM student_sites WHERE slug=?)'
        );
        $stmt->execute([$slug, $slug]);
        if ((int)$stmt->fetchColumn() === 0) return $slug;
    }
    return $base . '-' . bin2hex(random_bytes(3));
}

/** A first-time PIN: four digits, never a run or a repeat. */
function onboarding_make_pin(): string {
    do {
        $pin = (string)random_int(1000, 9999);
    } while (preg_match('/^(\d)\1{3}$/', $pin) || in_array($pin, ['1234','4321','0000'], true));
    return $pin;
}

/**
 * Create one learner and everything that belongs to them.
 * Returns ['learner_id','username','display_name','pin'] or throws.
 */
function onboard_learner(array $input, int $actorUserId): array {
    $displayName = trim((string)($input['display_name'] ?? ''));
    if ($displayName === '') throw new InvalidArgumentException('A first name or nickname is needed.');
    if (mb_strlen($displayName) > 60) $displayName = mb_substr($displayName, 0, 60);

    $consentOn = trim((string)($input['guardian_consent_on'] ?? ''));
    if ($consentOn === '' || strtotime($consentOn) === false) {
        throw new InvalidArgumentException('A guardian consent date is required before an account can be created.');
    }

    $scope = ($input['consent_scope'] ?? '') === 'learning_and_sponsor_updates'
        ? 'learning_and_sponsor_updates'
        : 'learning_only';

    $cohortId = (int)($input['cohort_id'] ?? 0) ?: null;
    $ageBand  = mb_substr(trim((string)($input['age_band'] ?? '')), 0, 40);
    $gender   = in_array($input['gender'] ?? '', ['girl','boy','other'], true)
        ? $input['gender'] : 'not_recorded';
    $lang     = isset(supported_langs()[$input['preferred_lang'] ?? '']) ? $input['preferred_lang'] : 'en';

    $slug = onboarding_make_slug($displayName);
    $pin  = onboarding_make_pin();

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO learners
                (username, display_name, age_band, gender, preferred_lang, pin_hash,
                 must_change_pin, cohort_id, guardian_consent_on, consent_scope, created_by)
             VALUES (?,?,?,?,?,?,1,?,?,?,?)'
        );
        $stmt->execute([
            $slug, $displayName, $ageBand, $gender, $lang,
            password_hash($pin, PASSWORD_DEFAULT),
            $cohortId, $consentOn, $scope, $actorUserId,
        ]);
        $learnerId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare('INSERT INTO student_sites (learner_id, slug, title) VALUES (?,?,?)');
        $stmt->execute([$learnerId, $slug, $displayName . "'s page"]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Files are created after the transaction commits: a failed write should
    // not roll back an account, and the editor recreates anything missing.
    site_seed($slug, $displayName);

    audit('learner.create', [
        'actor_user_id' => $actorUserId,
        'subject_type'  => 'learner',
        'subject_id'    => $learnerId,
        'detail'        => 'username=' . $slug . ' consent=' . $consentOn . ' scope=' . $scope,
    ]);

    return [
        'learner_id'   => $learnerId,
        'username'     => $slug,
        'display_name' => $displayName,
        'pin'          => $pin,
    ];
}

/** Issue a fresh first-time PIN for a learner who has forgotten theirs. */
function reset_learner_pin(int $learnerId, int $actorUserId): string {
    $pin = onboarding_make_pin();
    $stmt = db()->prepare(
        'UPDATE learners
            SET pin_hash=?, must_change_pin=1, failed_attempts=0, locked_until=NULL
          WHERE id=?'
    );
    $stmt->execute([password_hash($pin, PASSWORD_DEFAULT), $learnerId]);
    audit('learner.pin_reset', [
        'actor_user_id' => $actorUserId,
        'subject_type'  => 'learner',
        'subject_id'    => $learnerId,
    ]);
    return $pin;
}

/**
 * Parse pasted roster rows for bulk onboarding.
 * Format: display name, age band, guardian consent date (YYYY-MM-DD), scope, gender
 * Only the name and the consent date are required.
 */
function parse_roster_rows(string $raw): array {
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', trim($raw)) ?: [] as $index => $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = array_map('trim', str_getcsv($line));
        if (strtolower($parts[0] ?? '') === 'display name') continue;   // header row
        $rows[] = [
            'line'                => $index + 1,
            'display_name'        => $parts[0] ?? '',
            'age_band'            => $parts[1] ?? '',
            'guardian_consent_on' => $parts[2] ?? '',
            'consent_scope'       => ($parts[3] ?? '') === 'learning_and_sponsor_updates'
                                     ? 'learning_and_sponsor_updates' : 'learning_only',
            'gender'              => strtolower(trim($parts[4] ?? '')),
        ];
    }
    return $rows;
}
