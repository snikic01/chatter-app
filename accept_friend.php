<?php
session_start();
require_once 'db_chatter.php';

// Provera da li imamo ID onoga čiji zahtev prihvatamo i da li smo ulogovani
if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $sender_id = (int)$_GET['id'];
    $my_id = (int)$_SESSION['user_id'];

    // Menjamo status iz 'pending' u 'accepted'
    // Gledamo red gde je pošiljalac onaj čiji je ID stigao, a primalac MI
    $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE user_id = ? AND friend_id = ?");
    $stmt->execute([$sender_id, $my_id]);
}

// Vraćamo se na dashboard
header("Location: dashboard.php");
exit();
