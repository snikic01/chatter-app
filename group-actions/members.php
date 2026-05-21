<?php
// group-actions/members.php

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;

if ($group_id <= 0) {
    echo json_encode(["success" => false, "message" => "Group ID je obavezan!"]);
    exit;
}

// POPRAVLJENO: Druga grana sada preskače vlasnika grupe pomoću podupita, sprečavajući dupliranje!
$query = "SELECT CAST(u.id AS CHAR) as id, u.username, 1 AS is_owner 
          FROM users u 
          JOIN chat_groups g ON u.id = g.owner_id 
          WHERE g.id = ?
          
          UNION 
          
          SELECT CAST(u.id AS CHAR) as id, u.username, 0 AS is_owner 
          FROM users u 
          JOIN group_members gm ON u.id = gm.user_id 
          WHERE gm.group_id = ? 
          AND gm.user_id != (SELECT owner_id FROM chat_groups WHERE id = ?)";

$stmt = $pdo->prepare($query);
// Važno: Sada imamo 3 znaka pitanja, pa ID grupe prosleđujemo tri puta u execute!
$stmt->execute([$group_id, $group_id, $group_id]);
$members = $stmt->fetchAll();

echo json_encode(["success" => true, "members" => $members]);
exit;
