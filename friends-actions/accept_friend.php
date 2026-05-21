<?php
// friend-actions/accept_friend.php

$sender_id = isset($inputData['friend_id']) ? intval($inputData['friend_id']) : 0;

$stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE sender_id = ? AND receiver_id = ?");
$stmt->execute([$sender_id, $user_id]);

echo json_encode(["success" => true, "message" => "Zahtev prihvaćen!"]);
exit;
