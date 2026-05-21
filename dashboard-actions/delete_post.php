<?php
// dashboard-actions/delete_post.php
if ($post_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID objave je obavezan za brisanje!"]);
    exit;
}

// Brišemo samu objavu
$stmt = $pdo->prepare("DELETE FROM admin_news WHERE id = ?");
$stmt->execute([$post_id]);

echo json_encode(["success" => true, "message" => "Objava je uspešno uklonjena sa table!"]);
exit;
