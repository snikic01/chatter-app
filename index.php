<?php
session_start();
require_once 'db_chatter.php';

$error = "";

// REGISTRACIJA
if (isset($_POST['register'])) {
    $u = trim($_POST['username']);
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$u, $p]);
        $success = "Uspešna registracija! Sad se uloguj.";
    } catch (Exception $e) {
        $error = "Korisničko ime je zauzeto.";
    }
}

// LOGIN
if (isset($_POST['login'])) {
    $u = trim($_POST['username']);
    $p = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch();

    if ($user && password_verify($p, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Pogrešni podaci.";
    }
}
?>
