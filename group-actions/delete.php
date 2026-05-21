<?php
// group-actions/delete.php

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;

$stmt = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$owner_id = $stmt->fetchColumn();

if ($owner_id != $user_id) {
    echo json_encode(["success" => false, "message" => "Nemate ovlašćenje da obrišete ovu grupu!"]);
    exit;
}

$pdo->beginTransaction();
$pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$group_id]);
$pdo->prepare("DELETE FROM private_messages WHERE group_id = ?")->execute([$group_id]);
$pdo->prepare("DELETE FROM chat_groups WHERE id = ?")->execute([$group_id]);
$pdo->commit();

echo json_encode(["success" => true, "message" => "Grupa je obrisana!"]);
exit;
