<?php
// POPRAVLJENO: Ako user_id iz globalnog opsega stigne kao 0, pronalazimo ga preko username-a
if ((!isset($user_id) || $user_id <= 0) && !empty($username)) {
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $user_id = $stmtUser->fetchColumn() ?: 0;
}

try {
    $query = "SELECT 
                an.id,
                an.title,
                an.content,
                an.created_at,
                an.type,
                (SELECT COUNT(*) FROM news_likes nl WHERE nl.news_id = an.id) AS likes_count,
                (SELECT COUNT(*) FROM news_comments nc WHERE nc.news_id = an.id) AS comments_count,
                (SELECT COUNT(*) FROM news_likes nl WHERE nl.news_id = an.id AND nl.user_id = ?) AS user_liked
              FROM admin_news an
              ORDER BY an.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $postsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $posts = [];
    foreach ($postsRaw as $row) {
        $posts[] = [
            "id" => intval($row['id']),
            "title" => $row['title'],
            "content" => $row['content'],
            "created_at" => $row['created_at'],
            "type" => $row['type'],
            "likes_count" => intval($row['likes_count']),
            "comments_count" => intval($row['comments_count']),
            "is_liked" => intval($row['user_liked']), // Ključ koji Kotlin models traži
            "user_liked" => intval($row['user_liked'])
        ];
    }

    echo json_encode([
        "success" => true,
        "is_admin" => $is_admin,
        "posts" => $posts
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
