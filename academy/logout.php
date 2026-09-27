<?php
/**
 * Sign a learner out.
 *
 * POST with a valid token only, matching the sponsor side. A plain GET does
 * nothing: otherwise any page anywhere could sign a child out mid-lesson by
 * loading this URL in an image tag.
 */
require_once __DIR__ . '/../includes/learner_auth.php';
learner_session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_learner_csrf();
    logout_learner();
}

header('Location: ' . app_url('academy/login.php'));
