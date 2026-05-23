<?php
// private-actions/list_chats.php

// POPRAVLJENO: Koristimo tvoj originalni upit sa is_online i parametrima, 
// ali spajamo tabelu 'friends' kako bismo prikazali samo prihvaćene prijatelje!
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
          JOIN friends f ON (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
          WHERE f.status = 'accepted' AND u.id != ?
          ORDER BY last_message_time DESC, u.username ASC";

$stmt = $pdo->prepare($query);

// POPRAVLJENO: Prosleđujemo tačno onoliko parametara koliko upitnika imamo u SQL-u (sada ukupno 8)
$stmt->execute([
    $user_id, $user_id, // Za prvi podupit (last_message)
    $user_id, $user_id, // Za drugi podupit (last_message_time)
    $user_id,           // Za treći podupit (unread_count)
    $user_id, $user_id, // Za JOIN friends uslov (user_id i friend_id smera)
    $user_id            // Za WHERE filter (osim tebe)
]);
$chats = $stmt->fetchAll();

echo json_encode(["success" => true, "chats" => $chats]);
exit;
