<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$group_id = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // 1. Proveri da li je korisnik koji izlazi trenutno VLASNIK
    $stmtOwner = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
    $stmtOwner->execute([$group_id]);
    $current_owner = $stmtOwner->fetchColumn();

    // 2. Obriši korisnika iz članova
    $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?")
        ->execute([$group_id, $my_id]);

    // 3. Ako je on bio vlasnik, nađi novog
    if ($current_owner == $my_id) {
        // Tražimo najstarijeg sledećeg člana (onaj koji je prvi u bazi posle bivšeg vlasnika)
        $stmtNext = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? ORDER BY id ASC LIMIT 1");
        $stmtNext->execute([$group_id]);
        $next_owner = $stmtNext->fetchColumn();

        if ($next_owner) {
            // Postoji još neko u grupi, prebacujemo mu vlasništvo
            $pdo->prepare("UPDATE chat_groups SET owner_id = ? WHERE id = ?")
                ->execute([$next_owner, $group_id]);
        } else {
            // Nema više nikoga, grupa je prazna - brišemo celu grupu iz baze
            $pdo->prepare("DELETE FROM chat_groups WHERE id = ?")->execute([$group_id]);
            $pdo->prepare("DELETE FROM private_messages WHERE group_id = ?")->execute([$group_id]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
}

header("Location: dashboard.php");
exit();
