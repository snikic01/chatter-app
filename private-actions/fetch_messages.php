<?php
// private-actions/fetch_messages.php

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID prijatelja nedostaje!"]);
    exit;
}

try {
    // 1. STROGA PROVERA PRIJATELJSTVA: Dopusti prolaz samo ako je status 'accepted'
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM friends 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
          AND status = 'accepted'
    ");
    $stmtCheck->execute([$my_id, $chat_user_id, $chat_user_id, $my_id]);
    $isFriend = $stmtCheck->fetchColumn() > 0;

    if (!$isFriend) {
        echo json_encode([
            "success" => false, 
            "message" => "Nemate pravo pristupa istoriji poruka. Korisnik vam nije prihvaćeni prijatelj!"
        ]);
        exit;
    }

    // 2. Označavamo poruke kao viđene pri ulasku u čet (sada je bezbedno jer znamo da su prijatelji)
    $pdo->prepare("
        UPDATE private_messages 
        SET seen = 1 
        WHERE sender_id = ? AND receiver_id = ? AND seen = 0
    ")->execute([$chat_user_id, $my_id]);

    // 3. Tvoj originalni i stabilni SQL upit za istoriju poruka
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
        // MAPIRANJE: Zadržan ključ 'date' za bezbedan rad substringBefore u Kotlinu!
        $messages[] = [
            "username" => $row['username'],
            "message"  => $row['message'],
            "date"     => $row['created_at'], 
            "is_mine"  => (intval($row['sender_id']) === intval($my_id))
        ];
    }

    echo json_encode([
        "success" => true,
        "messages" => $messages
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
