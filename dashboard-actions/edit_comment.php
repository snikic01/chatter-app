<?php
// dashboard-actions/edit_comment.php
$comment_id   = isset($inputData['comment_id']) ? intval($inputData['comment_id']) : 0;
$new_text     = isset($inputData['comment_text']) ? trim($inputData['comment_text']) : '';

if ($comment_id <= 0 || empty($new_text)) {
    echo json_encode(["success" => false, "message" => "Podaci za izmenu su nepotpuni!"]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM news_comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment_author_id = $stmt->fetchColumn();

if ($user_id === intval($comment_author_id)) {
    $update = $pdo->prepare("UPDATE news_comments SET comment_text = ? WHERE id = ?");
    $update->execute([$new_text, $comment_id]);
    
    echo json_encode(["success" => true, "message" => "Komentar uspešno izmenjen!"]);
} else {
    echo json_encode(["success" => false, "message" => "Možete menjati samo sopstvene komentare!"]);
}
exit;
