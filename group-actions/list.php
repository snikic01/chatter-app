<?php
// group-actions/list.php

$query = "SELECT cg.id, cg.name, cg.owner_id, u2.username as owner_name, (cg.owner_id = ?) as is_owner 
          FROM chat_groups cg
          INNER JOIN group_members gm ON cg.id = gm.group_id
          JOIN users u2 ON cg.owner_id = u2.id
          WHERE gm.user_id = ?
          ORDER BY cg.id ASC";
          
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $user_id]);
$groups = $stmt->fetchAll();

$outputGroups = [];
foreach ($groups as $group) {
    $unreadQuery = "SELECT COUNT(*) FROM private_messages pm
                    WHERE pm.group_id = ? AND pm.sender_id != ?
                    AND NOT EXISTS (
                        SELECT 1 FROM group_message_seen gms 
                        WHERE gms.message_id = pm.id AND gms.user_id = ?
                    )";
    $unreadStmt = $pdo->prepare($unreadQuery);
    $unreadStmt->execute([$group['id'], $user_id, $user_id]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    $outputGroups[] = [
        "id" => (int)$group['id'],
        "name" => $group['name'],
        "owner_name" => $group['owner_name'],
        "is_owner" => $group['is_owner'] ? 1 : 0, 
        "unread_count" => $unreadCount
    ];
}

echo json_encode(["success" => true, "groups" => $outputGroups]);
exit;
