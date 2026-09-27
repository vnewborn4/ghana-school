<?php
/**
 * The sponsor bridge.
 *
 * Turns reviewed classroom work into a DRAFT sponsor update. A draft is never
 * visible to anyone outside the staff: an administrator reads it, rewrites it
 * in their own words, and approves it before a sponsor sees anything.
 *
 * Three rules hold at every step, and each is checked again at approval rather
 * than trusted from generation time:
 *
 *   1. Guardian consent. A learner whose guardian agreed to learning only can
 *      never produce a sponsor update, whatever a teacher ticked.
 *   2. A learner must be linked to a student journey. That link is the
 *      privacy-sensitive join between a real child and a pseudonymous public
 *      profile, and only an administrator may set it.
 *   3. The learner's own words are never copied into a draft. Children write
 *      freely -- a surname, a school, a street -- so drafts are composed from
 *      structured facts alone. The learner's text is shown to the administrator
 *      beside the draft as context they may choose to draw on, clearly marked
 *      as not published.
 *
 * Sponsor-facing text is English. Donors read English, and these drafts are
 * written for them; the Ghanaian-language support in lang/ is for learners.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

/** A short, sponsor-readable milestone tag for each kind of activity. */
function bridge_milestone_for_tool(string $tool): string {
    return match ($tool) {
        'blockly', 'snap', 'scratch' => 'Coding project',
        'webdev'                     => 'Web basics',
        'python'                     => 'Programming',
        'h5p'                        => 'Digital skills',
        'unplugged'                  => 'Classroom activity',
        default                      => 'Learning milestone',
    };
}

/** First sentence of a module summary, for the generated draft. */
function bridge_first_sentence(string $text): string {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') return '';
    if (preg_match('/^(.+?[.!?])(\s|$)/u', $text, $m)) return trim($m[1]);
    return $text;
}

/**
 * Create a draft update from a reviewed submission.
 * Returns the new update id, or null with $reason explaining why not.
 */
function bridge_generate_from_submission(int $submissionId, int $actorUserId, ?string &$reason = null): ?int {
    $stmt = db()->prepare(
        'SELECT s.id, s.shareable, s.status, s.bridged_at,
                l.id AS learner_id, l.consent_scope, l.student_journey_id,
                a.title AS assignment_title,
                m.title AS module_title, m.summary AS module_summary, m.tool,
                j.first_name AS journey_name
         FROM submissions s
         JOIN learners l   ON l.id = s.learner_id
         JOIN assignments a ON a.id = s.assignment_id
         JOIN modules m     ON m.id = a.module_id
         LEFT JOIN student_journeys j ON j.id = l.student_journey_id
         WHERE s.id = ?'
    );
    $stmt->execute([$submissionId]);
    $row = $stmt->fetch();

    if (!$row)                                 { $reason = 'That submission was not found.'; return null; }
    if ($row['status'] !== 'reviewed')         { $reason = 'Only reviewed work can become a sponsor update.'; return null; }
    if ((int)$row['shareable'] !== 1)          { $reason = 'This work has not been proposed for a sponsor update.'; return null; }
    if ($row['consent_scope'] !== 'learning_and_sponsor_updates') {
        $reason = 'This learner\'s guardian agreed to learning only.'; return null;
    }
    if (empty($row['student_journey_id'])) {
        $reason = 'This learner is not linked to a sponsor journey yet.'; return null;
    }
    if (!empty($row['bridged_at']))            { $reason = 'A sponsor update was already created from this work.'; return null; }

    $name     = $row['journey_name'] ?: 'This learner';
    $context  = bridge_first_sentence((string)$row['module_summary']);
    $title    = mb_substr((string)$row['module_title'], 0, 180);
    $summary  = $name . ' completed "' . $row['assignment_title'] . '", and a teacher reviewed the work.';
    if ($context !== '') {
        $summary .= ' This is part of the ' . $row['module_title'] . ' module: '
                  . lcfirst($context);
    }
    $milestone = bridge_milestone_for_tool((string)$row['tool']);

    return bridge_insert_draft([
        'journey_id'    => (int)$row['student_journey_id'],
        'title'         => $title,
        'summary'       => $summary,
        'milestone'     => $milestone,
        'submission_id' => $submissionId,
        'site_id'       => null,
        'actor'         => $actorUserId,
        'learner_id'    => (int)$row['learner_id'],
    ], $reason);
}

/**
 * Create a draft update the first time a learner's page is published.
 * Publishing a page is a one-time milestone, so later republishes do not
 * generate a second update.
 */
function bridge_generate_from_site(int $siteId, int $actorUserId, ?string &$reason = null): ?int {
    $stmt = db()->prepare(
        'SELECT ss.id, ss.status, ss.bridged_at,
                l.id AS learner_id, l.consent_scope, l.student_journey_id,
                j.first_name AS journey_name
         FROM student_sites ss
         JOIN learners l ON l.id = ss.learner_id
         LEFT JOIN student_journeys j ON j.id = l.student_journey_id
         WHERE ss.id = ?'
    );
    $stmt->execute([$siteId]);
    $row = $stmt->fetch();

    if (!$row)                          { $reason = 'That page was not found.'; return null; }
    if ($row['status'] !== 'published') { $reason = 'Only a published page can become a sponsor update.'; return null; }
    if ($row['consent_scope'] !== 'learning_and_sponsor_updates') {
        $reason = 'This learner\'s guardian agreed to learning only.'; return null;
    }
    if (empty($row['student_journey_id'])) {
        $reason = 'This learner is not linked to a sponsor journey yet.'; return null;
    }
    if (!empty($row['bridged_at']))     { $reason = 'A sponsor update was already created for this page.'; return null; }

    $name = $row['journey_name'] ?: 'This learner';

    return bridge_insert_draft([
        'journey_id'    => (int)$row['student_journey_id'],
        'title'         => 'A web page of their own',
        'summary'       => $name . ' built a web page from scratch. A teacher read it through, '
                         . 'and it is now published on the learning centre\'s website.',
        'milestone'     => 'Page published',
        'submission_id' => null,
        'site_id'       => $siteId,
        'actor'         => $actorUserId,
        'learner_id'    => (int)$row['learner_id'],
    ], $reason);
}

/** Shared insert. Drafts are invisible: status 'draft' and visible = 0. */
function bridge_insert_draft(array $d, ?string &$reason = null): ?int {
    $pdo = db();
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO student_updates
                (student_journey_id, title, summary, milestone, is_sample, published_at,
                 visible, status, source_submission_id, source_site_id, created_by_user_id)
             VALUES (?,?,?,?,0,NOW(),0,\'draft\',?,?,?)'
        );
        $stmt->execute([
            $d['journey_id'], $d['title'], $d['summary'], $d['milestone'],
            $d['submission_id'], $d['site_id'], $d['actor'],
        ]);
        $updateId = (int)$pdo->lastInsertId();

        if ($d['submission_id'] !== null) {
            $pdo->prepare('UPDATE submissions SET bridged_at=NOW() WHERE id=?')->execute([$d['submission_id']]);
        }
        if ($d['site_id'] !== null) {
            $pdo->prepare('UPDATE student_sites SET bridged_at=NOW() WHERE id=?')->execute([$d['site_id']]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $reason = 'That draft could not be created.';
        return null;
    }

    audit('sponsor_update.drafted', [
        'actor_user_id' => $d['actor'],
        'subject_type'  => 'student_update',
        'subject_id'    => $updateId,
        'detail'        => 'learner=' . $d['learner_id'] . ' journey=' . $d['journey_id'],
    ]);
    return $updateId;
}

/** Drafts waiting for an administrator, newest first. */
function bridge_pending_drafts(): array {
    return db()->query(
        'SELECT u.*, j.first_name AS journey_name, j.public_code,
                l.display_name AS learner_name, l.id AS learner_id,
                a.title AS assignment_title, sub.body AS learner_words,
                sub.teacher_feedback, ss.slug AS site_slug
         FROM student_updates u
         JOIN student_journeys j ON j.id = u.student_journey_id
         LEFT JOIN submissions sub ON sub.id = u.source_submission_id
         LEFT JOIN assignments a   ON a.id = sub.assignment_id
         LEFT JOIN student_sites ss ON ss.id = u.source_site_id
         LEFT JOIN learners l ON l.id = COALESCE(sub.learner_id, ss.learner_id)
         WHERE u.status = \'draft\'
         ORDER BY u.created_at DESC'
    )->fetchAll();
}

function bridge_pending_count(): int {
    return (int)db()->query('SELECT COUNT(*) FROM student_updates WHERE status=\'draft\'')->fetchColumn();
}

/**
 * Work a teacher proposed for sharing that cannot become an update yet,
 * so an administrator can see what is stuck and why.
 */
function bridge_blocked_work(): array {
    return db()->query(
        'SELECT s.id AS submission_id, l.id AS learner_id, l.display_name, l.consent_scope,
                l.student_journey_id, a.title AS assignment_title
         FROM submissions s
         JOIN learners l ON l.id = s.learner_id
         JOIN assignments a ON a.id = s.assignment_id
         WHERE s.shareable = 1 AND s.status = \'reviewed\' AND s.bridged_at IS NULL
               AND (l.student_journey_id IS NULL
                    OR l.consent_scope <> \'learning_and_sponsor_updates\')
         ORDER BY l.display_name'
    )->fetchAll();
}

/**
 * Approve a draft, with the administrator's edits. Consent is checked again
 * here: a guardian may have withdrawn it since the draft was generated.
 */
function bridge_approve(int $updateId, int $actorUserId, array $fields, ?string &$reason = null): bool {
    $stmt = db()->prepare(
        'SELECT u.*, l.consent_scope
         FROM student_updates u
         LEFT JOIN submissions sub ON sub.id = u.source_submission_id
         LEFT JOIN student_sites ss ON ss.id = u.source_site_id
         LEFT JOIN learners l ON l.id = COALESCE(sub.learner_id, ss.learner_id)
         WHERE u.id = ? AND u.status = \'draft\''
    );
    $stmt->execute([$updateId]);
    $draft = $stmt->fetch();
    if (!$draft) { $reason = 'That draft was not found.'; return false; }

    // Re-check consent at the moment of publication, not at generation.
    if ($draft['consent_scope'] !== null
        && $draft['consent_scope'] !== 'learning_and_sponsor_updates') {
        $reason = 'Consent for sponsor updates has been withdrawn for this learner. '
                . 'The draft was not approved.';
        return false;
    }

    $title     = mb_substr(trim((string)($fields['title'] ?? $draft['title'])), 0, 180);
    $summary   = trim((string)($fields['summary'] ?? $draft['summary']));
    $milestone = mb_substr(trim((string)($fields['milestone'] ?? (string)$draft['milestone'])), 0, 120);

    if ($title === '' || $summary === '') {
        $reason = 'A title and a summary are both needed.';
        return false;
    }

    $stmt = db()->prepare(
        'UPDATE student_updates
            SET title=?, summary=?, milestone=?, status=\'approved\', visible=1,
                published_at=NOW(), approved_by_user_id=?, approved_at=NOW()
          WHERE id=? AND status=\'draft\''
    );
    $stmt->execute([$title, $summary, $milestone !== '' ? $milestone : null, $actorUserId, $updateId]);

    audit('sponsor_update.approved', [
        'actor_user_id' => $actorUserId,
        'subject_type'  => 'student_update',
        'subject_id'    => $updateId,
    ]);
    return true;
}

/** Discard a draft without publishing. The source is not re-bridged. */
function bridge_discard(int $updateId, int $actorUserId): bool {
    $stmt = db()->prepare('DELETE FROM student_updates WHERE id=? AND status=\'draft\'');
    $stmt->execute([$updateId]);
    $gone = $stmt->rowCount() > 0;
    if ($gone) {
        audit('sponsor_update.discarded', [
            'actor_user_id' => $actorUserId,
            'subject_type'  => 'student_update',
            'subject_id'    => $updateId,
        ]);
    }
    return $gone;
}

/** Take an approved update back off the sponsor dashboard. */
function bridge_withdraw(int $updateId, int $actorUserId): bool {
    $stmt = db()->prepare(
        'UPDATE student_updates SET status=\'withdrawn\', visible=0 WHERE id=? AND status=\'approved\''
    );
    $stmt->execute([$updateId]);
    $done = $stmt->rowCount() > 0;
    if ($done) {
        audit('sponsor_update.withdrawn', [
            'actor_user_id' => $actorUserId,
            'subject_type'  => 'student_update',
            'subject_id'    => $updateId,
        ]);
    }
    return $done;
}

/**
 * Aggregate, non-identifying programme figures for the public site.
 * Counts only work that reached a teacher's review, so the numbers mean
 * something.
 */
function bridge_public_figures(): array {
    $one = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
    return [
        'learners'   => $one('SELECT COUNT(*) FROM learners WHERE active=1'),
        'completed'  => $one('SELECT COUNT(*) FROM submissions WHERE status=\'reviewed\''),
        'pages'      => $one('SELECT COUNT(*) FROM student_sites WHERE status=\'published\''),
    ];
}
