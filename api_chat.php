<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

    // Spajamo tabele preko JOIN-a da bismo Androidu poslali tekstualno korisnicko ime
    $query = "SELECT u.username, pm.message, pm.created_at 
              FROM private_messages pm 
              JOIN users u ON pm.sender_id = u.id 
              WHERE pm.group_id = ? 
              ORDER BY pm.created_at ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$group_id]);
    $rows = $stmt->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "sent_at" => $row['created_at'] // Mapiramo created_at u sent_at za Android
        ];
    }

    echo json_encode([
        "success" => true,
        "messages" => $messages
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "messages" => [], "error" => $e->getMessage()]);
    exit;
}
?>
