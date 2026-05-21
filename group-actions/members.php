<?php
// group-actions/members.php

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;

if ($group_id <= 0) {
    echo json_encode(["success" => false, "message" => "Group ID je obavezan!"]);
    exit;
}

// POPRAVLJENO: Koristimo UNIX_TIMESTAMP da bazu osiguramo od promašaja formata (300 sekundi = 5 minuta)
$query = "SELECT CAST(u.id AS CHAR) as id, u.username, 1 AS is_owner, 
          (IF(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(u.last_seen) <= 300, 1, 0)) as is_online
          FROM users u 
          JOIN chat_groups g ON u.id = g.owner_id 
          WHERE g.id = ?
          
          UNION 
          
          SELECT CAST(u.id AS CHAR) as id, u.username, 0 AS is_owner,
          (IF(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(u.last_seen) <= 300, 1, 0)) as is_online
          FROM users u 
          JOIN group_members gm ON u.id = gm.user_id 
          WHERE gm.group_id = ? 
          AND gm.user_id != (SELECT owner_id FROM chat_groups WHERE id = ?)";

$stmt = $pdo->prepare($query);
$stmt->execute([$group_id, $group_id, $group_id]);
$members = $stmt->fetchAll();

echo json_encode(["success" => true, "members" => $members]);
exit;
