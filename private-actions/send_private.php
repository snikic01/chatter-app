<?php
// private-actions/send_private.php
// $pdo i $my_id (ili $user_id) su bezbedno nasleđeni iz glavnog api_private.php rutera!

// Hvatanje ID-ja pošiljaoca (tebe)
$trenutni_user_id = isset($my_id) ? intval($my_id) : (isset($user_id) ? intval($user_id) : 0);

// Hvatanje ID-ja primaoca (sagovornika) - pokrivamo sve moguće nazive varijabli iz rutera i inputa
$trenutni_chat_user_id = 0;
if (isset($chat_user_id) && intval($chat_user_id) > 0) {
    $trenutni_chat_user_id = intval($chat_user_id);
} elseif (isset($allInputs['chat_user_id'])) {
    $trenutni_chat_user_id = intval($allInputs['chat_user_id']);
}

// Hvatanje teksta poruke - pokrivamo i $message i $message_text i $allInputs
$trenutna_poruka = '';
if (isset($message_text) && !empty(trim($message_text))) {
    $trenutna_poruka = trim($message_text);
} elseif (isset($message) && !empty(trim($message))) {
    $trenutna_poruka = trim($message);
} elseif (isset($allInputs['message'])) {
    $trenutna_poruka = trim($allInputs['message']);
}

// Validacija osnovnih podataka
if ($trenutni_user_id <= 0 || $trenutni_chat_user_id <= 0 || empty($trenutna_poruka)) {
    echo json_encode(["success" => false, "message" => "Nevalidni podaci za slanje privatne poruke!"]);
    exit;
}

try {
    // 1. BEZBEDNOSNA PROVERA: Da li su korisnici prihvaćeni prijatelji?
    $stmtCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM friends 
        WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) 
          AND status = 'accepted'
    ");
    $stmtCheck->execute([$trenutni_user_id, $trenutni_chat_user_id, $trenutni_chat_user_id, $trenutni_user_id]);
    $isFriend = $stmtCheck->fetchColumn() > 0;

    if (!$isFriend) {
        echo json_encode([
            "success" => false, 
            "message" => "Slanje poruke nije dozvoljeno jer niste prijatelji sa ovim korisnikom!"
        ]);
        exit;
    }

    // 2. UPIS PORUKE: Ako je provera prošla, poruka se bezbedno upisuje
    $stmt = $pdo->prepare("
        INSERT INTO private_messages (sender_id, receiver_id, group_id, message, seen, created_at) 
        VALUES (?, ?, NULL, ?, 0, NOW())
    ");
    
    if ($stmt->execute([$trenutni_user_id, $trenutni_chat_user_id, $trenutna_poruka])) {
        echo json_encode([
            "success" => true, 
            "message" => "Poruka uspešno poslata!",
            "message_id" => $pdo->lastInsertId() // Korisno za Android da odmah zna ID poruke
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Greška pri upisu u bazu!"]);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška pri slanju: " . $e->getMessage()]);
    exit;
}
?>
