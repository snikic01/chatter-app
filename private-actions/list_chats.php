<?php
// private-actions/list_chats.php

if (!isset($my_id) || $my_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID korisnika nedostaje u list_chats!"]);
    exit;
}

try {
    // Koristimo tvoj provereni SQL upit, ali potpuno usklađen sa unifikovanim :my_id parametrom
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
              WHERE u.id != :my_id
                AND u.id IN (
                    SELECT IF(user_id = :my_id, friend_id, user_id) 
                    FROM friends 
                    WHERE (user_id = :my_id OR friend_id = :my_id) AND status = 'accepted'
                )
              ORDER BY last_message_time DESC, u.username ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':my_id' => $my_id]);
    $chats = $stmt->fetchAll();

    echo json_encode(["success" => true, "chats" => $chats]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška u list_chats: " . $e->getMessage()]);
    exit;
}
?>
