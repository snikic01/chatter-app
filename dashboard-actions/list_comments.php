<?php
// POPRAVLJENO: Eksplicitno čitamo post_id iz svih dostupnih bafera da nikada ne bude 0
$trenutni_post_id = 0;
if (isset($post_id) && $post_id > 0) {
    $trenutni_post_id = $post_id;
} elseif (isset($_GET['post_id'])) {
    $trenutni_post_id = intval($_GET['post_id']);
} elseif (isset($inputData['post_id'])) {
    $trenutni_post_id = intval($inputData['post_id']);
}

if ($trenutni_post_id <= 0) {
    echo json_encode(["success" => false, "message" => "Post ID je neispravan unutar akcije!"]);
    exit;
}

try {
    // Upit koji povlači sve komentare za izabranu objavu
    $stmt = $pdo->prepare("
        SELECT 
            nc.id, 
            nc.news_id, 
            nc.user_id, 
            nc.comment_text, 
            nc.created_at, 
            u.username 
        FROM news_comments nc
        JOIN users u ON nc.user_id = u.id
        WHERE nc.news_id = ?
        ORDER BY nc.created_at ASC
    ");
    $stmt->execute([$trenutni_post_id]);
    $commentsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // POPRAVLJENO: Dupliramo ključ unutar niza tako da u JSON-u šaljemo i "username" i "commenter_name"
    // Na ovaj način šta god Android ViewModel tražio unutar optString-a, uspešno će pročitati!
    $comments = [];
    foreach ($commentsRaw as $row) {
        $comments[] = [
            "id" => intval($row['id']),
            "news_id" => intval($row['news_id']),
            "user_id" => intval($row['user_id']),
            "comment_text" => $row['comment_text'],
            "created_at" => $row['created_at'],
            "username" => $row['username'],             // Pokriva tvoj DashboardViewModel poziv
            "commenter_name" => $row['username']       // Pokriva stare i zamenske verzije modela
        ];
    }

    // Vraćamo čist i usaglašen JSON odgovor na telefon
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
