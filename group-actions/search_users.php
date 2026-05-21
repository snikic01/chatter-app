<?php
// group-actions/search_users.php

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
$query    = isset($inputData['query']) ? trim($inputData['query']) : '';

if (empty($query)) {
    echo json_encode(["success" => true, "users" => []]);
    exit;
}

// Tražimo korisnike čije ime sadrži ukucani tekst, ali preskačemo one koji su već u grupi
$stmt = $pdo->prepare("SELECT username FROM users 
                       WHERE username LIKE ? 
                       AND id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)
                       LIMIT 5");
                       
$stmt->execute(["%" . $query . "%", $group_id]);
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(["success" => true, "users" => $users]);
exit;
