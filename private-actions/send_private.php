<?php
// private-actions/send_private.php
// $pdo i $my_id su bezbedno nasleđeni iz glavnog api_private.php rutera!

// POPRAVLJENO: Hvata ID i parametre bez obzira da li ih telefon šalje kroz GET URL ili POST JSON body
$trenutni_user_id = isset($my_id) ? intval($my_id) : (isset($user_id) ? intval($user_id) : 0);
$trenutni_chat_user_id = isset($chat_user_id) ? intval($chat_user_id) : (isset($_GET['chat_user_id']) ? intval($_GET['chat_user_id']) : 0);
$trenutna_poruka = isset($message) ? trim($message) : (isset($_GET['message']) ? trim($_GET['message']) : '');

if ($trenutni_user_id <= 0 || $trenutni_chat_user_id <= 0 || empty($trenutna_poruka)) {
    echo json_encode(["success" => false, "message" => "Nevalidni podaci za slanje privatne poruke!"]);
    exit;
}

try {
    // POPRAVLJENO: Upisujemo ispravan sender_id u bazu ($trenutni_user_id)
    $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message, seen, created_at) VALUES (?, ?, NULL, ?, 0, NOW())");
    
    if ($stmt->execute([$trenutni_user_id, $trenutni_chat_user_id, $trenutna_poruka])) {
        echo json_encode(["success" => true, "message" => "Poruka uspešno poslata!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Greška pri upisu u bazu!"]);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška pri slanju: " . $e->getMessage()]);
    exit;
}
?>
