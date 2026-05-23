<?php
// private-actions/list_chats.php

// Osiguravamo se da imamo username ulogovanog korisnika sa telefona
$trenutni_username = '';
if (isset($username) && !empty($username)) {
    $trenutni_username = $username;
} elseif (isset($_GET['username'])) {
    $trenutni_username = trim($_GET['username']);
} elseif (isset($inputData['username'])) {
    $trenutni_username = trim($inputData['username']);
}

if (empty($trenutni_username)) {
    echo json_encode(["success" => false, "message" => "Korisničko ime nedostaje u list_chats!"]);
    exit;
}

try {
    // 1. Sami pronalazimo ID korisnika iz baze preko username-a radi sigurnosti
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$trenutni_username]);
    $lokalni_my_id = $stmtUser->fetchColumn() ?: 0;

    if ($lokalni_my_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik nije pronađen u bazi!"]);
        exit;
    }

    // 2. Upit koji pronalazi isključivo PRIHVAĆENE prijatelje u tabeli f (friends)
    // i spaja ih sa tabelom u (users) da bismo izvukli njihova imena
    $query = "SELECT u.id, u.username 
              FROM users u
              JOIN friends f ON (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
              WHERE f.status = 'accepted' AND u.id != ?";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$lokalni_my_id, $lokalni_my_id, $lokalni_my_id]);
    $friends = $stmt->fetchAll();

    $chats = [];
    foreach ($friends as $friend) {
        $f_id = $friend['id'];

        // Izvlačimo poslednju privatnu poruku između tebe i tog prijatelja
        $stmtMsg = $pdo->prepare("
            SELECT message, created_at 
            FROM private_messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmtMsg->execute([$lokalni_my_id, $f_id, $f_id, $lokalni_my_id]);
        $lastMsgRow = $stmtMsg->fetch();
        
        $last_message = $lastMsgRow ? $lastMsgRow['message'] : "Nema poruka. Započni čet!";
        $last_time = $lastMsgRow ? $lastMsgRow['created_at'] : "";

        // Računamo nepročitane poruke koje je taj prijatelj poslao tebi
        $stmtUnread = $pdo->prepare("
            SELECT COUNT(*) 
            FROM private_messages 
            WHERE sender_id = ? AND receiver_id = ? AND seen = 0
        ");
        $stmtUnread->execute([$f_id, $lokalni_my_id]);
        $unread_count = $stmtUnread->fetchColumn() ?: 0;

        $chats[] = [
            "id" => $f_id,
            "username" => $friend['username'],
            "last_message" => $last_message,
            "last_time" => $last_time,
            "unread_count" => intval($unread_count)
        ];
    }

    // Vraćamo čist i ispravan niz prijatelja nazad u Android aplikaciju
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
