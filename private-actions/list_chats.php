<?php
// private-actions/list_chats.php

// Eksplicitno čitamo username ulogovanog korisnika sa telefona da zaobiđemo PHP scope bagove
$trenutni_username = '';
if (isset($username) && !empty($username)) {
    $trenutni_username = $username;
} elseif (isset($_GET['username'])) {
    $trenutni_username = trim($_GET['username']);
} elseif (isset($inputData['username'])) {
    $trenutni_username = trim($inputData['username']);
}

if (empty($trenutni_username)) {
    echo json_encode(["success" => false, "message" => "Korisničko ime nedostaje u list_chats!"]);
    exit;
}

try {
    // 1. Sami izvlačimo tvoj pravi ID iz baze preko prosleđenog imena
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$trenutni_username]);
    $pravi_vlasnik_id = $stmtUser->fetchColumn() ?: 0;

    if ($pravi_vlasnik_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik nije pronađen u bazi podataka!"]);
        exit;
    }

    // 2. Tvoj originalni i stabilni SQL upit, zaključan ISKLJUČIVO na prihvaćene prijatelje!
    $query = "SELECT u.id, u.username,
              (IF(u.last_seen >= NOW() - INTERVAL 5 MINUTE, 1, 0)) as is_online,
              (SELECT pm.message FROM private_messages pm 
               WHERE pm.group_id IS NULL AND (
                     (pm.sender_id = u.id AND pm.receiver_id = ?) 
                  OR (pm.sender_id = ? AND pm.receiver_id = u.id)
               ) ORDER BY pm.id DESC LIMIT 1) as last_message,
              (SELECT pm.created_at FROM private_messages pm 
               WHERE pm.group_id IS NULL AND (
                     (pm.sender_id = u.id AND pm.receiver_id = ?) 
                  OR (pm.sender_id = ? AND pm.receiver_id = u.id)
               ) ORDER BY pm.id DESC LIMIT 1) as last_message_time,
              (SELECT COUNT(*) FROM private_messages pm 
               WHERE pm.group_id IS NULL AND pm.sender_id = u.id AND pm.receiver_id = ? AND pm.seen = 0) as unread_count
              FROM users u
              JOIN friends f ON (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
              WHERE f.status = 'accepted' AND u.id != ?
              ORDER BY last_message_time DESC, u.username ASC";

    $stmt = $pdo->prepare($query);
    
    // Prosleđujemo sigurno izračunati ID tačno 8 puta za svaki upitnik u SQL-u
    $stmt->execute([
        $pravi_vlasnik_id, $pravi_vlasnik_id, 
        $pravi_vlasnik_id, $pravi_vlasnik_id, 
        $pravi_vlasnik_id, 
        $pravi_vlasnik_id, $pravi_vlasnik_id, 
        $pravi_vlasnik_id
    ]);
    $chats = $stmt->fetchAll();

    // Vraćamo uspešan odgovor nazad na telefon
    echo json_encode(["success" => true, "chats" => $chats]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
