<?php
// friend-actions/accept_friend.php

$sender_id = isset($inputData['friend_id']) ? intval($inputData['friend_id']) : 0;

// Ti prihvataš, što znači da je on poslao (user_id = on, friend_id = ti)
$stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ?");
$stmt->execute([$sender_id, $user_id]);

echo json_encode(["success" => true, "message" => "Zahtev prihvaćen!"]);
exit;
