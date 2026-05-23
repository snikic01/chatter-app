<?php
// private-actions/fetch_messages.php

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID prijatelja nedostaje unutar fetch akcije!"]);
    exit;
}

try {
    // Automatski označavamo sve primljene poruke od tog prijatelja kao pročitane čim uđeš u čet
    $pdo->prepare("
        UPDATE private_messages 
        SET seen = 1 
        WHERE sender_id = ? AND receiver_id = ? AND seen = 0
    ")->execute([$chat_user_id, $my_id]);

    // Povlačimo kompletnu istoriju dopisivanja hronološki
    $stmtChat = $pdo->prepare("
        SELECT pm.sender_id, pm.message, pm.created_at, u.username
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE (pm.sender_id = ? AND pm.receiver_id = ?) OR (pm.sender_id = ? AND pm.receiver_id = ?)
        ORDER BY pm.created_at ASC
    ");
    $stmtChat->execute([$my_id, $chat_user_id, $chat_user_id, $my_id]);
    $rows = $stmtChat->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "sent_at" => $row['created_at'],
            "is_mine" => (intval($row['sender_id']) === $my_id)
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
