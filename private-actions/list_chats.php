<?php
// private-actions/list_chats.php

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
    // Koristimo imenovani parametar :username tačno onako kako je prošlo u tvojoj konzoli!
    $query = "SELECT 
                u.id, 
                u.username,
                COALESCE(
                    (SELECT pm.message 
                     FROM private_messages pm 
                     WHERE (pm.sender_id = (SELECT id FROM users WHERE username = :username) AND pm.receiver_id = u.id) 
                        OR (pm.sender_id = u.id AND pm.receiver_id = (SELECT id FROM users WHERE username = :username))
                     ORDER BY pm.created_at DESC LIMIT 1), 
                    'Nema poruka. Započni čet!'
                ) AS last_message,
                COALESCE(
                    (SELECT pm.created_at 
                     FROM private_messages pm 
                     WHERE (pm.sender_id = (SELECT id FROM users WHERE username = :username) AND pm.receiver_id = u.id) 
                        OR (pm.sender_id = u.id AND pm.receiver_id = (SELECT id FROM users WHERE username = :username))
                     ORDER BY pm.created_at DESC LIMIT 1), 
                    ''
                ) AS last_time,
                (SELECT COUNT(*) 
                 FROM private_messages pm 
                 WHERE pm.sender_id = u.id 
                   AND pm.receiver_id = (SELECT id FROM users WHERE username = :username) 
                   AND pm.seen = 0) AS unread_count
            FROM users u
            JOIN friends f ON (f.user_id = (SELECT id FROM users WHERE username = :username) AND f.friend_id = u.id)
                           OR (f.friend_id = (SELECT id FROM users WHERE username = :username) AND f.user_id = u.id)
            WHERE f.status = 'accepted' 
              AND u.username != :username";

    $stmt = $pdo->prepare($query);
    
    // PDO drajver sam automatski mapira reč :username na svim mestima u upitu odjednom!
    $stmt->execute([':username' => $trenutni_username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $chats = [];
    foreach ($rows as $row) {
        $chats[] = [
            "id" => intval($row['id']),
            "username" => $row['username'],
            "last_message" => $row['last_message'],
            "last_time" => $row['last_time'],
            "unread_count" => intval($row['unread_count'])
        ];
    }

    // Šaljemo čist i ispravan JSON odgovor nazad na Android telefon
    echo json_encode([
        "success" => true,
        "chats" => $chats
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška: " . $e->getMessage()]);
    exit;
}
?>
