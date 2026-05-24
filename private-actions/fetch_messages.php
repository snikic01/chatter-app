<?php
// private-actions/fetch_messages.php

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID prijatelja (chat_user_id) nedostaje za povlačenje poruka!"]);
    exit;
}

try {
    // 1. Provera statusa prijateljstva
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM friends 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
          AND status = 'accepted'
    ");
    $stmtCheck->execute([$my_id, $chat_user_id, $chat_user_id, $my_id]);
    if ($stmtCheck->fetchColumn() <= 0) {
        echo json_encode(["success" => false, "message" => "Nemate pravo pristupa. Korisnik vam nije prihvaćeni prijatelj!"]);
        exit;
    }

    // 2. Automatsko markiranje nepročitanih poruka kao viđenih pri ulasku u čet
    $pdo->prepare("UPDATE private_messages SET seen = 1 WHERE sender_id = ? AND receiver_id = ? AND seen = 0")->execute([$chat_user_id, $my_id]);

    // 3. Povlačenje kompletne istorije privatnih poruka
    $stmtChat = $pdo->prepare("
        SELECT pm.sender_id, pm.message, pm.created_at, pm.seen, u.username
        FROM private_messages pm
        JOIN users u ON pm.sender_id = u.id
        WHERE pm.group_id IS NULL AND ((pm.sender_id = ? AND pm.receiver_id = ?) OR (pm.sender_id = ? AND pm.receiver_id = ?))
        ORDER BY pm.created_at ASC
    ");
    $stmtChat->execute([$my_id, $chat_user_id, $chat_user_id, $my_id]);
    $rows = $stmtChat->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        $messages[] = [
            "username" => $row['username'],
            "message"  => $row['message'],
            "date"     => $row['created_at'], 
            "seen"     => intval($row['seen']) // Vraćamo eksplicitno 1 ili 0 za tvoj klijent
        ];
    }

    echo json_encode([
        "success"  => true,
        "messages" => $messages
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška u fetch_messages: " . $e->getMessage()]);
    exit;
}
?>
