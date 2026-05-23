<?php
if (empty($comment_id) && isset($_GET['comment_id'])) $comment_id = intval($_GET['comment_id']);

if ($comment_id <= 0 || $user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Korisnik ili ID komentara nedostaje!"]);
    exit;
}

// Provera prava: Samo admin (snikic01) ili vlasnik komentara može da briše
$stmtCheck = $pdo->prepare("SELECT user_id FROM news_comments WHERE id = ?");
$stmtCheck->execute([$comment_id]);
$commentOwner = $stmtCheck->fetchColumn();

// $is_admin je već definisan u glavnom api_dashboard.php fajlu
if ($commentOwner == $user_id || $is_admin) {
    $pdo->prepare("DELETE FROM news_comments WHERE id = ?")->execute([$comment_id]);
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Nemate ovlašćenje za brisanje ovog komentara!"]);
}
exit;
