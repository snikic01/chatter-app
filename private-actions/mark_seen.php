<?php
// private-actions/mark_seen.php

$chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : 0;

if ($chat_user_id > 0) {
    $stmt = $pdo->prepare("UPDATE private_messages SET seen = 1 WHERE group_id IS NULL AND sender_id = ? AND receiver_id = ?");
    $stmt->execute([$chat_user_id, $user_id]);
}

echo json_encode(["success" => true]);
exit;
