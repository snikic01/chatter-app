<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$requester_id = (int)$_GET['id'];

// Brišemo zahtev (samo ako je status bio 'pending')
$stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
$stmt->execute([$requester_id, $my_id]);

header("Location: dashboard.php");
exit();
