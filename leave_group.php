<?php
session_start();
require_once 'db_chatter.php';

// Uključujemo ispis grešaka samo za ovaj test ako ponovo dobiješ 500
// ini_set('display_errors', 1); 
// error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$group_id = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // 1. Provera trenutnog vlasnika
    $stmtOwner = $pdo->prepare("SELECT owner_id FROM chat_groups WHERE id = ?");
    $stmtOwner->execute([$group_id]);
    $current_owner = $stmtOwner->fetchColumn();

    // 2. Brisanje korisnika iz članova grupe
    $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?")
        ->execute([$group_id, $my_id]);

    // 3. Logika za prenos vlasništva ili totalno brisanje
    if ($current_owner == $my_id) {
        // Tražimo sledećeg člana po redu u bazi
        $stmtNext = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? ORDER BY id ASC LIMIT 1");
        $stmtNext->execute([$group_id]);
        $next_owner = $stmtNext->fetchColumn();

        if ($next_owner) {
            // Prebacujemo vlasništvo na sledećeg člana
            $pdo->prepare("UPDATE chat_groups SET owner_id = ? WHERE id = ?")
                ->execute([$next_owner, $group_id]);
        } else {
            // AKO SI BIO POSLEDNJI: Brišemo sve tragove grupe
            // Prvo seen logovi (zbog stranih ključeva), pa poruke, pa grupa
            $pdo->prepare("DELETE FROM group_message_seen WHERE message_id IN (SELECT id FROM private_messages WHERE group_id = ?)")
                ->execute([$group_id]);
                
            $pdo->prepare("DELETE FROM private_messages WHERE group_id = ?")
                ->execute([$group_id]);

            $pdo->prepare("DELETE FROM chat_groups WHERE id = ?")
                ->execute([$group_id]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Opciono: log_error($e->getMessage());
}

header("Location: dashboard.php");
exit();
