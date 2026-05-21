<?php
// dashboard-actions/toggle_like.php

if ($post_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID objave je obavezan!"]);
    exit;
}

// Proveravamo da li je korisnik već lajkovao ovu vest
$stmt = $pdo->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$like_id = $stmt->fetchColumn();

if ($like_id) {
    // Ako lajk postoji, uklanjamo ga (Unlike)
    $delete = $pdo->prepare("DELETE FROM news_likes WHERE id = ?");
    $delete->execute([$like_id]);
    echo json_encode([
        "success" => true, 
        "action_taken" => "unliked", 
        "message" => "Lajk je uklonjen."
    ]);
} else {
    // Ako lajk ne postoji, dodajemo ga (Like)
    $insert = $pdo->prepare("INSERT INTO news_likes (news_id, user_id, created_at) VALUES (?, ?, NOW())");
    $insert->execute([$post_id, $user_id]);
    echo json_encode([
        "success" => true, 
        "action_taken" => "liked", 
        "message" => "Objava je lajkovana."
    ]);
}
exit;
