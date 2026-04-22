<?php
session_start();
require_once 'db_chatter.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $group_id = (int)$_GET['id'];
    $my_id = $_SESSION['user_id'];

    // Provera: Da li je korisnik vlasnik grupe?
    $stmt = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch();

    if ($group && $group['owner_id'] == $my_id) {
        // 1. Obriši "seen" zapise za te poruke
        $pdo->prepare("DELETE FROM group_message_seen WHERE message_id IN (SELECT id FROM private_messages WHERE group_id = ?)")->execute([$group_id]);
        
        // 2. Obriši poruke
        $pdo->prepare("DELETE FROM private_messages WHERE group_id = ?")->execute([$group_id]);
        
        // 3. Obriši članove
        $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$group_id]);
        
        // 4. Obriši samu grupu
        $pdo->prepare("DELETE FROM chat_groups WHERE id = ?")->execute([$group_id]);
    }
}

header("Location: dashboard.php");
exit();
