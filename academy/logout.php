<?php
require_once __DIR__ . '/../includes/learner_auth.php';
learner_session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { verify_learner_csrf(); }
logout_learner();
header('Location: ' . app_url('academy/login.php'));
