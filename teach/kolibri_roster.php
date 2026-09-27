<?php
/**
 * Downloads the academy roster in Kolibri's bulk user import format, so the
 * centre's Kolibri server issues the same usernames the website does.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/centre_activity.php';

$user = require_role('teacher');

$cohortId = (int)($_GET['cohort'] ?? 0) ?: null;
$csv = centre_kolibri_roster_csv($cohortId);

audit('centre.roster_export', [
    'actor_user_id' => (int)$user['id'],
    'subject_type'  => 'cohort',
    'subject_id'    => $cohortId,
]);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Length: ' . (string)strlen($csv));
header('Content-Disposition: attachment; filename="kolibri-roster-' . date('Y-m-d') . '.csv"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
echo $csv;
