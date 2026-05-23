<?php
// private-actions/list_chats.php

// POPRAVLJENO: Uzimamo ID korisnika kako god da se zove promenljiva iz rutera api_private.php
$trenutni_user_id = 0;
if (isset($my_id) && $my_id > 0) {
    $trenutni_user_id = $my_id;
} elseif (isset($user_id) && $user_id > 0) {
    $trenutni_user_id = $user_id;
} elseif (isset($_GET['user_id'])) {
    $trenutni_user_id = intval($_GET['user_id']);
}

if ($trenutni_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID korisnika je neispravan unutar akcije!"]);
    exit;
}

try {
    // Tvoj originalni i stabilni SQL upit, spojen sa tabelom 'friends' za prihvaćene prijatelje
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
    
    // Prosleđujemo sigurno izračunati ID tačno 8 puta za svaki upitnik u SQL-u
    $stmt->execute([
        $trenutni_user_id, $trenutni_user_id, 
        $trenutni_user_id, $trenutni_user_id, 
        $trenutni_user_id, 
        $trenutni_user_id, $trenutni_user_id, 
        $trenutni_user_id
    ]);
    $chats = $stmt->fetchAll();

    // Vraćamo uspešan i čist JSON odgovor koji tvoj Android i model savršeno razumeju
    echo json_encode(["success" => true, "chats" => $chats]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška: " . $e->getMessage()]);
    exit;
}
?>
