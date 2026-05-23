<?php
if (empty($post_id) && isset($_GET['post_id'])) $post_id = intval($_GET['post_id']);

if ($post_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID objave nedostaje!"]);
    exit;
}

// Prvo brišemo sve lajkove i komentare vezane za taj post da ne pukne Foreign Key uslov u bazi
$pdo->prepare("DELETE FROM news_likes WHERE news_id = ?")->execute([$post_id]);
$pdo->prepare("DELETE FROM news_comments WHERE news_id = ?")->execute([$post_id]);

// Zatim brišemo samu objavu
$stmt = $pdo->prepare("DELETE FROM admin_news WHERE id = ?");
if ($stmt->execute([$post_id])) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Greška pri brisanju objave."]);
}
exit;
