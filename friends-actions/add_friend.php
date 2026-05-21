<?php
// friend-actions/add_friend.php

$friend_username = isset($inputData['friend_username']) ? trim($inputData['friend_username']) : '';

if (empty($friend_username)) {
    echo json_encode(["success" => false, "message" => "Korisničko ime je obavezno!"]);
    exit;
}

// Saznajemo ID korisnika kog želiš da dodaš
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$friend_username]);
$receiver_id = $stmt->fetchColumn();

if (!$receiver_id || $receiver_id == $user_id) {
    echo json_encode(["success" => false, "message" => "Nevaljan korisnik!"]);
    exit;
}

// Provera da li već postoji bilo kakav zahtev ili prijateljstvo u bazi
$stmtCheck = $pdo->prepare("SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
$stmtCheck->execute([$user_id, $receiver_id, $receiver_id, $user_id]);

if ($stmtCheck->fetch()) {
    echo json_encode(["success" => false, "message" => "Zahtev je već poslat ili ste već prijatelji!"]);
    exit;
}

// Upisujemo zahtev (ti si user_id, on je friend_id)
$stmtInsert = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
$stmtInsert->execute([$user_id, $receiver_id]);

echo json_encode(["success" => true, "message" => "Zahtev poslat!"]);
exit;
