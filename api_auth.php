<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Isključujemo ispisivanje HTML grešaka (da ne pokvare JSON)
ini_set('display_errors', 0);

// Povezivanje na bazu (uveri se da je putanja do db_chatter.php tačna)
require_once 'db_chatter.php';

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

try {
    // Uzimamo poruke iz tabele private_messages za grupu 8
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

    // Šaljemo čist JSON nazad u Android
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
