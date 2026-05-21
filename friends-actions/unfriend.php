<?php
// friend-actions/unfriend.php

$friend_id = isset($inputData['friend_id']) ? intval($inputData['friend_id']) : 0;

$stmt = $pdo->prepare("DELETE FROM friends WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
$stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);

echo json_encode(["success" => true, "message" => "Uklonjeno iz prijatelja!"]);
exit;
