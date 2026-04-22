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
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $cur_time = time();
    $time_elapsed = $cur_time - $time_ago;
    
    if ($time_elapsed <= 60) return "maločas";
    $minutes = round($time_elapsed / 60);
    if ($minutes <= 60) return "pre $minutes min";
    $hours = round($time_elapsed / 3600);
    if ($hours <= 24) return "pre $hours h";
    $days = round($time_elapsed / 86400);
    return "pre $days dana";
}



