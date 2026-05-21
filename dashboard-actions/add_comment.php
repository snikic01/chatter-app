<?php
// dashboard-actions/add_comment.php
$comment_text = isset($inputData['comment_text']) ? trim($inputData['comment_text']) : '';

if ($post_id <= 0 || empty($comment_text)) {
    echo json_encode(["success" => false, "message" => "Tekst komentara i ID objave su obavezni!"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO news_comments (news_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$post_id, $user_id, $comment_text]);

echo json_encode(["success" => true, "message" => "Komentar je uspešno dodat!"]);
exit;
