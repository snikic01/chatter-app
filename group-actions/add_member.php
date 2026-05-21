<?php
// group-actions/add_member.php

$group_id    = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
$new_member  = isset($inputData['new_member_username']) ? trim($inputData['new_member_username']) : '';

if ($group_id <= 0 || empty($new_member)) {
    echo json_encode(["success" => false, "message" => "Nevalidni podaci za dodavanje!"]);
    exit;
}

$userStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$userStmt->execute([$new_member]);
$new_user_row = $userStmt->fetch();

if (!$new_user_row) {
    echo json_encode(["success" => false, "message" => "Korisnik sa tim imenom ne postoji!"]);
    exit;
}

$new_user_id = intval($new_user_row['id']);

$checkStmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
$checkStmt->execute([$group_id, $new_user_id]);
if ($checkStmt->fetch()) {
    echo json_encode(["success" => false, "message" => "Korisnik je već član ove grupe!"]);
    exit;
}

$insertStmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
if ($insertStmt->execute([$group_id, $new_user_id])) {
    echo json_encode(["success" => true, "message" => "Korisnik uspešno dodat!"]);
} else {
    echo json_encode(["success" => false, "message" => "Greška pri upisu u bazu!"]);
}
exit;
