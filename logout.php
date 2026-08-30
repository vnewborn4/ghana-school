<?php
require_once 'includes/auth.php';
if($_SERVER['REQUEST_METHOD']==='POST'){ verify_csrf(); logout_user(); }
header('Location: index.php'); exit;
