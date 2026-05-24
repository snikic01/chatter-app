<?php
// private-actions/list_chats.php

if (!isset($my_id) || $my_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID korisnika nedostaje u list_chats!"]);
    exit;
}

try {
    // Upit koji spaja users i friends i garantuje filtriranje samo na accepted status
    $query = "SELECT u.id, u.username,
              (IF(u.last_seen >= NOW() - INTERVAL 5 MINUTE, 1, 0)) as is_online,
              COALESCE(
                  (SELECT pm.message FROM private_messages pm 
                   WHERE pm.group_id IS NULL AND (
                         (pm.sender_id = u.id AND pm.receiver_id = :my_id) 
                      OR (pm.sender_id = :my_id AND pm.receiver_id = u.id)
                   ) ORDER BY pm.id DESC LIMIT 1), 
                  'Nema poruka. Započni čet!'
              ) as last_message,
              (SELECT pm.created_at FROM private_messages pm 
               WHERE pm.group_id IS NULL AND (
                     (pm.sender_id = u.id AND pm.receiver_id = :my_id) 
                  OR (pm.sender_id = :my_id AND pm.receiver_id = u.id)
               ) ORDER BY pm.id DESC LIMIT 1) as last_message_time,
              (SELECT COUNT(*) FROM private_messages pm 
               WHERE pm.group_id IS NULL AND pm.sender_id = u.id AND pm.receiver_id = :my_id AND pm.seen = 0) as unread_count
              FROM users u
              JOIN friends f ON ((f.user_id = :my_id AND f.friend_id = u.id) OR (f.friend_id = :my_id AND f.user_id = u.id))
              WHERE u.id != :my_id
                AND f.status = 'accepted'
              GROUP BY u.id
              ORDER BY last_message_time DESC, u.username ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':my_id' => $my_id]);
    $chats = $stmt->fetchAll();

    $formattedChats = [];
    foreach ($chats as $chat) {
        $formattedChats[] = [
            "id" => intval($chat['id']),
            "username" => $chat['username'],
            "is_online" => intval($chat['is_online']),
            "last_message" => $chat['last_message'],
            "unread_count" => intval($chat['unread_count'])
        ];
    }

    echo json_encode([
        "success" => true, 
        "chats" => $formattedChats,
        "provera_koda" => "VERZIJA_2" // <--- DODAJ OVU SVEŽU LINIJU KODA OVDE!
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška u list_chats: " . $e->getMessage()]);
    exit;
}
?>
