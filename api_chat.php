<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

// Uvozimo tvoju pdo konekciju
require_once 'db_chatter.php';

if (!isset($pdo)) {
    echo json_encode([
        "success" => false,
        "message" => "Greška: PDO konekcija (\$pdo) nije pronađena!",
        "messages" => []
    ]);
    exit;
}

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

try {
    // Koristimo PDO pripremu i izvršavanje upita
    $stmt = $pdo->prepare("SELECT username, message, sent_at FROM private_messages WHERE group_id = ? ORDER BY sent_at ASC");
    $stmt->execute([$group_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "messages" => []
    ]);
    exit;
}
?>
