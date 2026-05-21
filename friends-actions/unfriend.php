<?php
// friend-actions/unfriend.php

$friend_id = isset($inputData['friend_id']) ? intval($inputData['friend_id']) : 0;

// Brišemo red bez obzira na to ko je započeo prijateljstvo
$stmt = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
$stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);

echo json_encode(["success" => true, "message" => "Uklonjeno iz prijatelja!"]);
exit;
