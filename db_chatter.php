<?php
$host = 'localhost';
$db   = 'chatter_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4'; // Proveri da li ovde imas ;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Baza nedostupna: " . $e->getMessage());
}
if (isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
}


