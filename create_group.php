<?php
session_start();
require_once 'db_chatter.php';

if (isset($_POST['group_name']) && isset($_SESSION['user_id'])) {
    $name = trim($_POST['group_name']);
    $owner = $_SESSION['user_id'];

    if (!empty($name)) {
        // 1. Napravi grupu
        $stmt = $pdo->prepare("INSERT INTO chat_groups (name, owner_id) VALUES (?, ?)");
        $stmt->execute([$name, $owner]);
        $group_id = $pdo->lastInsertId();

        // 2. Ubaci sebe kao prvog člana
        $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
        $stmt->execute([$group_id, $owner]);
    }
}
header("Location: dashboard.php");
