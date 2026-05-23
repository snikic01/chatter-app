<?php
// dashboard-actions/toggle_like.php

// Eksplicitno čitamo parametre iz URL Query stringa da nikada ne budu prazni
$trenutni_post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$trenutni_username = isset($_GET['username']) ? trim($_GET['username']) : '';

if ($trenutni_post_id <= 0 || empty($trenutni_username)) {
    echo json_encode(["success" => false, "message" => "ID objave i korisničko ime su obavezni!"]);
    exit;
}

try {
    // Pronalazimo pravi ID korisnika u bazi na osnovu korisničkog imena sa telefona
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$trenutni_username]);
    $skriveni_user_id = $stmtUser->fetchColumn() ?: 0;

    if ($skriveni_user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik ne postoji u bazi podataka!"]);
        exit;
    }

    // Proveravamo da li je ovaj korisnik već lajkovao ovu objavu
    $stmt = $pdo->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
    $stmt->execute([$trenutni_post_id, $skriveni_user_id]);
    $like_id = $stmt->fetchColumn();

    if ($like_id) {
        // Ako lajk već postoji u MySQL-u, uklanjamo ga (Unlike)
        $pdo->prepare("DELETE FROM news_likes WHERE id = ?")->execute([$like_id]);
        echo json_encode(["success" => true, "message" => "Lajk je uklonjen."]);
    } else {
        // Ako lajk ne postoji, upisujemo novi red u news_likes tabelu
        $pdo->prepare("INSERT INTO news_likes (news_id, user_id) VALUES (?, ?)")->execute([$trenutni_post_id, $skriveni_user_id]);
        echo json_encode(["success" => true, "message" => "Objava je uspešno lajkovana!"]);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
