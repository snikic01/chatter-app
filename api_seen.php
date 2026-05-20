<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0); error_reporting(0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST ?? $_GET;

        $action   = isset($inputData['action']) ? trim($inputData['action']) : 'mark';
    $group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
    
    // POPRAVLJENO: Čitamo direktno prosleđeni ID
    $user_id  = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;

    if (!$user_id || !$group_id) {
        echo json_encode(["success" => false, "message" => "Nevalidni podaci!"]);
        exit;
    }


    // --- 1. OZNAČI SVE PORUKE U GRUPI KAO SEEN ---
    if ($action === 'mark') {
        // Uzimamo sve poruke iz grupe koje ovaj korisnik još nije označio kao viđene
        $query = "INSERT IGNORE INTO group_message_seen (message_id, user_id, seen_at)
                  SELECT id, ?, NOW() FROM private_messages 
                  WHERE group_id = ? AND sender_id != ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $group_id, $user_id]);
        
        echo json_encode(["success" => true]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
}
?>
