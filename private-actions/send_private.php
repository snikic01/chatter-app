<?php
// private-actions/send_private.php

if ($trenutni_user_id <= 0 || $trenutni_chat_user_id <= 0 || empty($trenutna_poruka)) {
    echo json_encode(["success" => false, "message" => "Nevalidni ili nepotpuni podaci za slanje privatne poruke!"]);
    exit;
}

try {
    // Provera da li su pošiljalac i primalac zaista prihvaćeni prijatelji
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) FROM friends 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
          AND status = 'accepted'
    ");
    $stmtCheck->execute([$trenutni_user_id, $trenutni_chat_user_id, $trenutni_chat_user_id, $trenutni_user_id]);
    
    if ($stmtCheck->fetchColumn() <= 0) {
        echo json_encode(["success" => false, "message" => "Ne možete poslati poruku korisniku koji vam nije prihvaćeni prijatelj!"]);
        exit;
    }

    // Upis privatne poruke u bazu (group_id ostaje NULL jer je 1-na-1 čet)
    $stmt = $pdo->prepare("
        INSERT INTO private_messages (sender_id, receiver_id, group_id, message, seen, created_at) 
        VALUES (?, ?, NULL, ?, 0, NOW())
    ");
    
    if ($stmt->execute([$trenutni_user_id, $trenutni_chat_user_id, $trenutna_poruka])) {
        echo json_encode(["success" => true, "message" => "Poruka uspešno poslata!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Greška pri upisu poruke u bazu podataka!"]);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška pri slanju privatne poruke: " . $e->getMessage()]);
    exit;
}
?>
