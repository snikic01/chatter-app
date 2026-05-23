<?php
// Osiguravamo se da imamo pristup PDO konekciji i parametrima iz api_dashboard.php
// $pdo, $user_id, i $username su vec definisani u glavnom fajlu!

try {
    // SQL upit koji povlači sve objave iz admin_news tabele.
    // Preko podupita (Subqueries) računamo ukupan broj lajkova i komentara za svaki post,
    // i proveravamo da li je trenutno ulogovani korisnik lajkovao tu objavu (user_liked).
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

    // Dupliramo ključeve u nizu tako da JSON šalje nazive koje tvoj Android ViewModel i models traže
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
            "is_liked" => intval($row['user_liked']), // Ključ koji proverava da li je srce crveno ili sivo
            "user_liked" => intval($row['user_liked'])
        ];
    }

    // Vraćamo uspešan JSON odgovor sa nizom objava (posts) koji Android ViewModel traži
    echo json_encode([
        "success" => true,
        "is_admin" => $is_admin,
        "posts" => $posts
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Greška u list_posts: " . $e->getMessage()
    ]);
    exit;
}
?>
