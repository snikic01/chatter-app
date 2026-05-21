<?php
// group-actions/create.php

$group_name = isset($inputData['group_name']) ? trim($inputData['group_name']) : '';
if (empty($group_name)) {
    echo json_encode(["success" => false, "message" => "Ime grupe je obavezno!"]);
    exit;
}

$pdo->beginTransaction();
$stmt = $pdo->prepare("INSERT INTO chat_groups (name, owner_id) VALUES (?, ?)");
$stmt->execute([$group_name, $user_id]);
$group_id = $pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
$stmt->execute([$group_id, $user_id]);
$pdo->commit();

echo json_encode(["success" => true, "message" => "Grupa kreirana!"]);
exit;
