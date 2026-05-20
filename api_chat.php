<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Isključujemo HTML greške da ne bi pokvarile JSON format
ini_set('display_errors', 0);
error_reporting(0);

// Uvozimo tvoju konekciju ka bazi (proveri da li se fajl zove db_chatter.php)
require_once 'db_chatter.php';

// Ako $conn ne postoji (pogrešan naziv varijable u db_chatter.php), prekidamo sa greškom
if (!isset($conn)) {
    echo json_encode([
        "success" => false,
        "message" => "Greška: Konekcija sa bazom (\$conn) nije pronađena!",
        "messages" => []
    ]);
    exit;
}

// Hvatanje group_id parametra iz URL-a (difolt je 8)
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

try {
    // VAŽNO: Proveri da li se tvoja tabela zove tačno 'private_messages' i da li ima ove kolone
    $query = "SELECT username, message, sent_at FROM private_messages WHERE group_id = ? ORDER BY sent_at ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
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

    // Šaljemo ispravan JSON format koji Android očekuje
    echo json_encode([
        "success" => true,
        "messages" => $messages
    ]);
    exit;

} catch (Exception $e) {
    // U slučaju bilo kakve greške u SQL-u, vraćamo bezbedan prazan niz poruka
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "messages" => []
    ]);
    exit;
}
?>
