<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
ini_set('display_errors', 0);
error_reporting(0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Čitamo group_id (Podržava i GET parametre sa telefona)
    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 8;

    // DODATO: Selektujemo i pm.id jer nam treba za unakrsno traženje seen statusa
    $query = "SELECT pm.id, u.username, pm.message, pm.created_at 
              FROM private_messages pm 
              JOIN users u ON pm.sender_id = u.id 
              WHERE pm.group_id = ? 
              ORDER BY pm.created_at ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$group_id]);
    $rows = $stmt->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        // --- NOVI PODUPIT: Za svaki ID poruke izvlačimo tekstualna imena ljudi koji su je videli ---
        $seenQuery = "SELECT u.username FROM group_message_seen gms
                      JOIN users u ON gms.user_id = u.id
                      WHERE gms.message_id = ?
                      ORDER BY gms.seen_at ASC";
                      
        $seenStmt = $pdo->prepare($seenQuery);
        $seenStmt->execute([$row['id']]);
        $seenList = $seenStmt->fetchAll(PDO::FETCH_COLUMN); // Vraća čist niz stringova: ["nikic", "snikic01"]

        $messages[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "sent_at" => $row['created_at'],
            "seen_by"  => $seenList // <-- DODATO: Šaljemo spisak imena u Android aplikaciju!
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
