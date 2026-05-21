<?php
// friend-actions/add_friend.php

$friend_username = isset($inputData['friend_username']) ? trim($inputData['friend_username']) : '';

if (empty($friend_username)) {
    echo json_encode(["success" => false, "message" => "Korisničko ime je obavezno!"]);
    exit;
}

// Saznajemo ID korisnika
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$friend_username]);
$receiver_id = $stmt->fetchColumn();

if (!$receiver_id || $receiver_id == $user_id) {
    echo json_encode(["success" => false, "message" => "Nevaljan korisnik!"]);
    exit;
}

// Provera da li već postoji bilo kakav zahtev ili prijateljstvo
$stmtCheck = $pdo->prepare("SELECT 1 FROM friends WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
$stmtCheck->execute([$user_id, $receiver_id, $receiver_id, $user_id]);

if ($stmtCheck->fetch()) {
    echo json_encode(["success" => false, "message" => "Zahtev je već poslat ili ste već prijatelji!"]);
    exit;
}

// Upisujemo 'pending' zahtev u bazu
$stmtInsert = $pdo->prepare("INSERT INTO friends (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmtInsert->execute([$user_id, $receiver_id]);

echo json_encode(["success" => true, "message" => "Zahtev poslat!"]);
exit;
