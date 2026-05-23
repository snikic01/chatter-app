<?php
if (empty($title) && isset($_GET['title'])) $title = trim($_GET['title']);
if (empty($content) && isset($_GET['content'])) $content = trim($_GET['content']);
if (empty($board_color) && isset($_GET['board_color'])) $board_color = trim($_GET['board_color']);

if (empty($title) || empty($content)) {
    echo json_encode(["success" => false, "message" => "Naslov i sadržaj su obavezni!"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO admin_news (title, content, created_at, type) VALUES (?, ?, NOW(), ?)");
if ($stmt->execute([$title, $content, $board_color])) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Greška pri kreiranju objave."]);
}
exit;
