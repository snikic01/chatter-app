<?php
// private-actions/send_private.php

$chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : 0;
$message      = isset($inputData['message']) ? trim($inputData['message']) : '';

if ($chat_user_id <= 0 || empty($message)) {
    echo json_encode(["success" => false, "message" => "Nevalidni podaci za slanje!"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message, seen) VALUES (?, ?, NULL, ?, 0)");
if ($insertStmt = $stmt->execute([$user_id, $chat_user_id, $message])) {
    echo json_encode(["success" => true, "message" => "Poruka uspešno poslata!"]);
} else {
    echo json_encode(["success" => false, "message" => "Greška pri upisu u bazu!"]);
}
exit;
