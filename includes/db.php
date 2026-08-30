<?php
$host = 'localhost';
$dbname = 'ghana_school';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If the database doesn't exist yet, we don't want to completely break the site, 
    // but we should log or display a message
    die("Database Connection failed: " . $e->getMessage() . ". Please ensure you have imported database.sql in phpMyAdmin.");
}
?>
