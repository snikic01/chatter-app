<?php
// private-actions/list_chats.php
// $pdo i $my_id su bezbedno nasleđeni iz glavnog api_private.php rutera

try {
    // SQL upit koji spaja users i friends da izvuče SAMO one sa statusom 'accepted'
    $query = "SELECT u.id, u.username 
              FROM users u
              JOIN friends f ON (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
              WHERE f.status = 'accepted' AND u.id != ?";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$my_id, $my_id, $my_id]);
    $friends = $stmt->fetchAll();

    $chats = [];
    foreach ($friends as $friend) {
        $f_id = $friend['id'];

        // Izvlačimo poslednju poruku između tebe i tog prijatelja iz private_messages tabele
        $stmtMsg = $pdo->prepare("
            SELECT message, created_at 
            FROM private_messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmtMsg->execute([$my_id, $f_id, $f_id, $my_id]);
        $lastMsgRow = $stmtMsg->fetch();
        
        $last_message = $lastMsgRow ? $lastMsgRow['message'] : "Nema poruka. Započni čet!";
        $last_time = $lastMsgRow ? $lastMsgRow['created_at'] : "";

        // Računamo nepročitane poruke koje je taj prijatelj poslao tebi (seen = 0)
        $stmtUnread = $pdo->prepare("
            SELECT COUNT(*) 
            FROM private_messages 
            WHERE sender_id = ? AND receiver_id = ? AND seen = 0
        ");
        $stmtUnread->execute([$f_id, $my_id]);
        $unread_count = $stmtUnread->fetchColumn() ?: 0;

        $chats[] = [
            "id" => $f_id,
            "username" => $friend['username'],
            "last_message" => $last_message,
            "last_time" => $last_time,
            "unread_count" => intval($unread_count)
        ];
    }

    echo json_encode([
        "success" => true,
        "chats" => $chats
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška u list_chats: " . $e->getMessage()]);
    exit;
}
?>
