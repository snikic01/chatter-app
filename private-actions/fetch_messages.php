<?php
// private-actions/fetch_messages.php

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID prijatelja nedostaje unutar fetch akcije!"]);
    exit;
}

try {
    // 1. Označavamo sve primljene poruke od tog prijatelja kao pročitane čim uđeš u čet
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
        // POPRAVLJENO MAPIRANJE: Šaljemo ključ 'date' koji tvoj Kotlin kod u PrivateScreen.kt striktno traži!
        $messages[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "date" => $row['created_at'], // Vraća "YYYY-MM-DD HH:MM:SS" format koji substringBefore bezbedno seče!
            "seen" => intval($row['seen'])
        ];
    }

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
