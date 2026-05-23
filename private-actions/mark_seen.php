<?php
// private-actions/mark_seen.php

if ($chat_user_id <= 0) {
    echo json_encode(["success" => false, "message" => "ID prijatelja nedostaje za mark akciju!"]);
    exit;
}

try {
    // Označavamo sve poruke koje je taj korisnik poslao meni kao pročitane
    $stmt = $pdo->prepare("
        UPDATE private_messages 
        SET seen = 1 
        WHERE sender_id = ? AND receiver_id = ? AND seen = 0
    ");
    $stmt->execute([$chat_user_id, $my_id]);

    echo json_encode(["success" => true, "message" => "Poruke uspešno označene kao viđene!"]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška u mark_seen: " . $e->getMessage()]);
    exit;
}
?>
