<?php
/**
 * Streams a learner's uploaded assignment file to a signed-in staff member.
 * Files are never reachable directly over HTTP.
 */
require_once __DIR__ . '/../includes/paths.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/student_sites.php';

require_role('teacher');

$stmt = db()->prepare('SELECT file_path, original_name FROM submissions WHERE id=?');
$stmt->execute([(int)($_GET['id'] ?? 0)]);
$submission = $stmt->fetch();

if (!$submission || empty($submission['file_path'])) {
    http_response_code(404);
    exit('That file was not found.');
}

$path = submission_absolute_path($submission['file_path']);
if ($path === null) { http_response_code(404); exit('That file was not found.'); }

$name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)($submission['original_name'] ?: basename($path)));

header('Content-Type: application/octet-stream');
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: attachment; filename="' . $name . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
