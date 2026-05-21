<?php
// private-actions/fetch_messages.php

$chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : 0;

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID korisnika je obavezan!"]);
    exit;
}

$query = "SELECT pm.id, u.username, pm.message, pm.created_at as date, pm.seen 
          FROM private_messages pm
          JOIN users u ON pm.sender_id = u.id
          WHERE pm.group_id IS NULL AND (
                (pm.sender_id = ? AND pm.receiver_id = ?)
             OR (pm.sender_id = ? AND pm.receiver_id = ?)
          ) ORDER BY pm.id ASC";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $chat_user_id, $chat_user_id, $user_id]);
$messages = $stmt->fetchAll();

echo json_encode(["success" => true, "messages" => $messages]);
exit;
