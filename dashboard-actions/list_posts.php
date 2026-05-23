<?php
// Osiguravamo se da imamo pristup PDO konekciji i parametrima iz api_dashboard.php
// $pdo, $user_id, i $username su vec definisani u glavnom fajlu!

try {
    // SQL upit koji povlači sve objave iz admin_news tabele.
    // Takođe preko podupita (Subqueries) računa ukupan broj lajkova i komentara za svaki post,
    // i proverava da li je trenutno ulogovani korisnik već lajkovao tu objavu.
    $query = "SELECT 
                an.id,
                an.title,
                an.content,
                an.created_at,
                an.type,
                (SELECT COUNT(*) FROM news_likes nl WHERE nl.news_id = an.id) AS likes_count,
                (SELECT COUNT(*) FROM news_comments nc WHERE nc.news_id = an.id) AS comments_count,
                (SELECT COUNT(*) FROM news_likes nl WHERE nl.news_id = an.id AND nl.user_id = ?) AS is_liked
              FROM admin_news an
              ORDER BY an.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vraćamo uspešan JSON odgovor sa nizom objava (posts) koji Android ViewModel traži
    echo json_encode([
        "success" => true,
        "is_admin" => $is_admin, // $is_admin je već izračunat u api_dashboard.php
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
