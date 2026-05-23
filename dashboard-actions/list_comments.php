<?php
// Osiguravamo se da imamo ispravan post_id sa telefona (bilo kroz POST ili GET)
if (empty($post_id) && isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
}

if ($post_id <= 0) {
    echo json_encode(["success" => false, "message" => "Post ID je neispravan!"]);
    exit;
}

try {
    // Upit koji spaja komentare sa tabelom users da bi povukao tačno ime autora (username)
    $stmt = $pdo->prepare("
        SELECT 
            nc.id, 
            nc.news_id, 
            nc.user_id, 
            nc.comment_text, 
            nc.created_at, 
            u.username AS commenter_name 
        FROM news_comments nc
        JOIN users u ON nc.user_id = u.id
        WHERE nc.news_id = ?
        ORDER BY nc.created_at ASC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vraćamo uspešan odgovor sa ključem "comments" u množini koji tvoj Android traži
    echo json_encode([
        "success" => true,
        "comments" => $comments
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Greška u list_comments: " . $e->getMessage()
    ]);
    exit;
}
?>
