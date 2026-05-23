<?php
$stmt = $pdo->prepare("
    SELECT nc.id, nc.news_id, nc.user_id, nc.comment_text, nc.created_at, u.username AS commenter_name 
    FROM news_comments nc
    JOIN users u ON nc.user_id = u.id
    WHERE nc.news_id = ?
    ORDER BY nc.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "comments" => $comments
]);
exit;
