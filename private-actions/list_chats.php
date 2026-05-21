<?php
// private-actions/list_chats.php

// Selektujemo sve korisnike iz tabele users osim tebe
$query = "SELECT u.id, u.username,
          (IF(u.last_seen >= NOW() - INTERVAL 5 MINUTE, 1, 0)) as is_online,
          (SELECT pm.message FROM private_messages pm 
           WHERE pm.group_id IS NULL AND (
                 (pm.sender_id = u.id AND pm.receiver_id = ?) 
              OR (pm.sender_id = ? AND pm.receiver_id = u.id)
           ) ORDER BY pm.id DESC LIMIT 1) as last_message,
          (SELECT pm.created_at FROM private_messages pm 
           WHERE pm.group_id IS NULL AND (
                 (pm.sender_id = u.id AND pm.receiver_id = ?) 
              OR (pm.sender_id = ? AND pm.receiver_id = u.id)
           ) ORDER BY pm.id DESC LIMIT 1) as last_message_time,
          (SELECT COUNT(*) FROM private_messages pm 
           WHERE pm.group_id IS NULL AND pm.sender_id = u.id AND pm.receiver_id = ? AND pm.seen = 0) as unread_count
          FROM users u
          WHERE u.id != ?
          ORDER BY last_message_time DESC, u.username ASC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$chats = $stmt->fetchAll();

echo json_encode(["success" => true, "chats" => $chats]);
exit;
