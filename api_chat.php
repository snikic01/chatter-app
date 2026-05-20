<?php
// 1. ISKLJUČIVO JSON ZAGLAVLJA (Sprečava mešanje HTML-a)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Isključi prikazivanje HTML grešaka ako nešto pukne
ini_set('display_errors', 0); 

// 2. POVEZIVANJE SA BAZOM 
// (Zameni sa tvojim tačnim podacima za konekciju ako nemaš db_chatter.php)
require_once 'db_chatter.php'; 

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

try {
    // Povlačimo poruke za grupu 8 iz tabele private_messages
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

    // 3. SLANJE ČISTOG JSON ODGOVORA TELEFONU
    echo json_encode([
        "success" => true,
        "messages" => $messages
    ]);
    
    // Ključno: Prekidamo izvršavanje da PHP ne bi učitao nikakav HTML ispod!
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
