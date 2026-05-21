<?php
// group-actions/members.php

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;

if ($group_id <= 0) {
    echo json_encode(["success" => false, "message" => "Group ID je obavezan!"]);
    exit;
}

// UNION: Prvo vuče vlasnika (1), pa spaja sa svim običnim članovima (0)
$query = "SELECT CAST(u.id AS CHAR) as id, u.username, 1 AS is_owner 
          FROM users u 
          JOIN chat_groups g ON u.id = g.owner_id 
          WHERE g.id = ?
          
          UNION 
          
          SELECT CAST(u.id AS CHAR) as id, u.username, 0 AS is_owner 
          FROM users u 
          JOIN group_members gm ON u.id = gm.user_id 
          WHERE gm.group_id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$group_id, $group_id]);
$members = $stmt->fetchAll();

echo json_encode(["success" => true, "members" => $members]);
exit;
