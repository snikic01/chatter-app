<?php
// private-actions/list_chats.php

// 🔍 PANCIRNI ZID: Ako ruter nije uspeo da pronađe tvoj pravi ID preko username-a,
// odmah prekidamo izvršavanje i vraćamo USPEŠAN prazan niz. 
// Ovo garantuje da se rezervni prikaz sa svim korisnicima nikada više ne može upaliti na telefonu!
if (!isset($my_id) || intval($my_id) <= 0) {
    echo json_encode([
        "success" => true,
        "chats" => [],
        "poruka_sistema" => "Korisnički ID je nevalidan ili prazan. Prikaz je bezbedno očišćen."
    ]);
    exit;
}

try {
    // KONAČAN I POTPUNO BEZBEDAN SQL UPIT
    $query = "SELECT 
                u.id, 
                u.username,
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
              -- Hermetički filter: Uzimamo samo ljude gde je status prijateljstva u ovom sekundu 'accepted'
              INNER JOIN (
                  SELECT IF(user_id = :my_id, friend_id, user_id) AS prijatelj_id 
                  FROM friends 
                  WHERE (user_id = :my_id OR friend_id = :my_id) AND status = 'accepted'
              ) f ON u.id = f.prijatelj_id
              WHERE u.id != :my_id
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
        "chats" => $formattedChats
    ]);
    exit;

} catch (Exception $e) {
    // Čak i ako baza baci bilo kakvu grešku, vraćamo success: true sa praznim nalogom da telefon ne bi povukao sve ljude
    echo json_encode([
        "success" => true,
        "chats" => [],
        "poruka_greske" => $e->getMessage()
    ]);
    exit;
}
?>
