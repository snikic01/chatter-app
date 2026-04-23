<?php
// Osiguraj da je sesija aktivna pre provere
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Ako je korisnik ulogovan, proveri da li je u međuvremenu banovan
if (isset($_SESSION['user_id']) && $_SESSION['username'] !== 'snikic01') {
    $my_id = $_SESSION['user_id'];
    $my_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];

    // 1. Provera USER bana
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$my_id]);
    $user_status = $stmt->fetchColumn();

    // 2. Provera IP bana
    $checkIp = $pdo->prepare("SELECT id FROM banned_ips WHERE ip_address = ?");
    $checkIp->execute([$my_ip]);
    $ip_is_banned = $checkIp->fetch();

    // Ako je bilo šta od ovoga istina, uništi sesiju i izbaci ga
    if ($user_status == 1 || $ip_is_banned) {
        session_unset();
        session_destroy();
        header("Location: index.php?error=banned");
        exit();
    }
}


