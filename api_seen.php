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
    
    // 1. Čitamo username koji nam stiže iz Androida
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $user_id  = 0;

    // 2. DINAMIČKI PRONAĐI USER_ID IZ BAZE PREKO USERNAME-A
    if (!empty($username)) {
        // Pretpostavljamo da ti se tabela zove 'users', a kolone 'id' i 'username'
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $userStmt->execute([$username]);
        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($userRow) {
            $user_id = intval($userRow['id']);
        }
    }

    // Ako i dalje nemamo validan ID korisnika ili grupe, prekidamo rad
    if (!$user_id || !$group_id) {
        echo json_encode(["success" => false, "message" => "Nevalidni podaci! User ID: $user_id, Group ID: $group_id"]);
        exit;
    }

    // --- 1. OZNAČI SVE PORUKE U GRUPI KAO SEEN ---
    if ($action === 'mark') {
        $query = "INSERT IGNORE INTO group_message_seen (message_id, user_id, seen_at)
                  SELECT id, ?, NOW() FROM private_messages 
                  WHERE group_id = ? AND sender_id != ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $group_id, $user_id]);
        
        echo json_encode(["success" => true]);
        exit;
    }
?>
