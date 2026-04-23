<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php"); exit();
}

$my_id = (int)$_SESSION['user_id'];
$group_id = (int)$_GET['id'];

try {
    // 1. Obriši člana
    $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $my_id]);

    // 2. Proveri da li je grupa ostala prazna
    $check = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
    $check->execute([$group_id]);
    
    if ($check->fetchColumn() == 0) {
        // Briši grupu ako nema nikog
        $pdo->prepare("DELETE FROM chat_groups WHERE id = ?")->execute([$group_id]);
    } else {
        // Ako je izašao vlasnik, postavi prvog sledećeg za vlasnika
        $pdo->prepare("UPDATE chat_groups SET owner_id = (SELECT user_id FROM group_members WHERE group_id = ? LIMIT 1) WHERE id = ? AND owner_id = ?")
            ->execute([$group_id, $group_id, $my_id]);
    }
} catch (Exception $e) {}

header("Location: dashboard.php");
exit();
