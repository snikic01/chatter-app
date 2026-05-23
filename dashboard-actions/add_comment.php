<?php
if (empty($post_id) && isset($_GET['post_id'])) $post_id = intval($_GET['post_id']);
if (empty($comment_text) && isset($_GET['comment_text'])) $comment_text = trim($_GET['comment_text']);

if ($post_id <= 0 || $user_id <= 0 || empty($comment_text)) {
    echo json_encode(["success" => false, "message" => "Nedostaju parametri za komentar!"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO news_comments (news_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
if ($stmt->execute([$post_id, $user_id, $comment_text])) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Greška pri upisu komentara."]);
}
exit;
