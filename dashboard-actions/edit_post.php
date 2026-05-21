<?php
// dashboard-actions/edit_post.php
if ($post_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID objave je obavezan za izmenu!"]);
    exit;
}

$title   = isset($inputData['title']) ? trim($inputData['title']) : '';
$content = isset($inputData['content']) ? trim($inputData['content']) : '';
$type    = isset($inputData['board_color']) ? trim($inputData['board_color']) : 'standard';

if (empty($title) || empty($content)) {
    echo json_encode(["success" => false, "message" => "Naslov i sadržaj ne mogu biti prazni!"]);
    exit;
}

$stmt = $pdo->prepare("UPDATE admin_news SET title = ?, content = ?, type = ? WHERE id = ?");
$stmt->execute([$title, $content, $type, $post_id]);

echo json_encode(["success" => true, "message" => "Objava je uspešno izmenjena!"]);
exit;
