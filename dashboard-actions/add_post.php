<?php
// dashboard-actions/add_post.php
$title   = isset($inputData['title']) ? trim($inputData['title']) : '';
$content = isset($inputData['content']) ? trim($inputData['content']) : '';
$type    = isset($inputData['board_color']) ? trim($inputData['board_color']) : 'standard'; // 'standard' ili 'urgent'

if (empty($title) || empty($content)) {
    echo json_encode(["success" => false, "message" => "Naslov i sadržaj objave su obavezni!"]);
    exit;
}

// Osiguravamo da se unosi isključivo jedna od dve dozvoljene boje/tipa
if ($type !== 'standard' && $type !== 'urgent') {
    $type = 'standard';
}

$stmt = $pdo->prepare("INSERT INTO admin_news (title, content, type, created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$title, $content, $type]);

echo json_encode(["success" => true, "message" => "Nova objava je uspešno postavljena na tablu!"]);
exit;
