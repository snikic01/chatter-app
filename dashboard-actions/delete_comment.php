<?php
// dashboard-actions/delete_comment.php
$comment_id = isset($inputData['comment_id']) ? intval($inputData['comment_id']) : 0;

if ($comment_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID komentara je obavezan!"]);
    exit;
}

// Provera vlasnika komentara
$stmt = $pdo->prepare("SELECT user_id FROM news_comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment_author_id = $stmt->fetchColumn();

if (!$comment_author_id) {
    echo json_encode(["success" => false, "message" => "Komentar ne postoji!"]);
    exit;
}

// Admin briše sve, običan korisnik samo svoj komentar
if ($is_admin || $user_id === intval($comment_author_id)) {
    $delete = $pdo->prepare("DELETE FROM news_comments WHERE id = ?");
    $delete->execute([$comment_id]);
    
    echo json_encode(["success" => true, "message" => "Komentar uspešno obrisan!"]);
} else {
    echo json_encode(["success" => false, "message" => "Nemate dozvolu za brisanje tuđih komentara!"]);
}
exit;
