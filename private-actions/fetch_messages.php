<?php
// private-actions/fetch_messages.php

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID prijatelja nedostaje unutar fetch akcije!"]);
    exit;
}

try {
    // 1. Označavamo sve primljene poruke od tog prijatelja kao pročitane (seen = 1)
    $pdo->prepare("
        UPDATE private_messages 
        SET seen = 1 
        WHERE sender_id = ? AND receiver_id = ? AND seen = 0
    ")->execute([$chat_user_id, $my_id]);

    // 2. Povlačimo kompletnu istoriju dopisivanja hronološki
    $stmtChat = $pdo->prepare("
        SELECT pm.id, pm.sender_id, pm.message, pm.created_at, u.username, pm.seen
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE (pm.sender_id = ? AND pm.receiver_id = ?) OR (pm.sender_id = ? AND pm.receiver_id = ?)
        ORDER BY pm.created_at ASC
    ");
    $stmtChat->execute([$my_id, $chat_user_id, $chat_user_id, $my_id]);
    $rows = $stmtChat->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        // POPRAVLJENO: Šaljemo ključeve "date" i "seen" koje tvoj originalni Kotlin kod striktno traži!
        $messages[] = [
            "id" => intval($row['id']),
            "username" => $row['username'],
            "message" => $row['message'],
            "date" => $row['created_at'], // Usaglašeno sa Ktor klijentom!
            "seen" => intval($row['seen']), // Vraća 1 ili 0
            "is_mine" => (intval($row['sender_id']) === intval($my_id))
        ];
    }

    // Vraćamo success true i niz poruka
    echo json_encode([
        "success" => true,
        "messages" => $messages
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška u fetch_messages: " . $e->getMessage()]);
    exit;
}
?>
