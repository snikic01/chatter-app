<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

// Otvaramo nezavisnu konekciju da izbegnemo $_SESSION provere iz db_chatter.php
$host = 'localhost';
$db   = 'chatter_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

    // Povlačenje istorije poruka za KontraverzneBiznismene (ID: 8)
    $stmt = $pdo->prepare("SELECT username, message, sent_at FROM private_messages WHERE group_id = ? ORDER BY sent_at ASC");
    $stmt->execute([$group_id]);
    $rows = $stmt->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "sent_at" => $row['sent_at']
        ];
    }

    echo json_encode([
        "success" => true,
        "messages" => $messages
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Greška sa bazom: " . $e->getMessage(),
        "messages" => []
    ]);
    exit;
}
?>
