<?php
// group-actions/kick.php

$group_id     = isset($inputData['group_id']) ? intval($inputData['group_id']) : 0;
$kick_user_id = isset($inputData['kick_user_id']) ? intval($inputData['kick_user_id']) : 0;

// Proveravamo da li je korisnik koji šalje zahtev zapravo vlasnik te grupe
$stmt = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$owner_id = (int)$stmt->fetchColumn();

if ($owner_id !== $user_id) {
    echo json_encode(["success" => false, "message" => "Nemate ovlašćenje da izbacujete članove!"]);
    exit;
}

if ($kick_user_id === $owner_id) {
    echo json_encode(["success" => false, "message" => "Ne možete izbaciti sami sebe!"]);
    exit;
}

// Brišemo člana iz grupe
$stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $kick_user_id]);

echo json_encode(["success" => true, "message" => "Korisnik izbačen!"]);
exit;
