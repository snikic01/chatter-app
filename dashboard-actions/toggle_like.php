<?php
// dashboard-actions/toggle_like.php

// POPRAVLJENO: Eksplicitno čitamo parametre iz URL-a (GET) ili JSON-a da nikada ne budu 0
$trenutni_post_id = 0;
if (isset($post_id) && $post_id > 0) {
    $trenutni_post_id = $post_id;
} elseif (isset($_GET['post_id'])) {
    $trenutni_post_id = intval($_GET['post_id']);
}

$trenutni_user_id = 0;
if (isset($user_id) && $user_id > 0) {
    $trenutni_user_id = $user_id;
} elseif (isset($_GET['user_id'])) {
    $trenutni_user_id = intval($_GET['user_id']);
}

if ($trenutni_post_id <= 0 || $trenutni_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID objave i ID korisnika su obavezni!"]);
    exit;
}

// Proveravamo da li je korisnik već lajkovao ovu vest
$stmt = $pdo->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
$stmt->execute([$trenutni_post_id, $trenutni_user_id]);
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
    $insert->execute([$trenutni_post_id, $trenutni_user_id]);
    echo json_encode([
        "success" => true, 
        "action_taken" => "liked", 
        "message" => "Objava je lajkovana."
    ]);
}
exit;
