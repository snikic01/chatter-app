<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
require_once 'db_chatter.php';

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

try {
    $query = "SELECT username, message, sent_at FROM private_messages WHERE group_id = ? ORDER BY sent_at ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
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
        "messages" => [],
        "error" => $e->getMessage()
    ]);
    exit;
}
?>
