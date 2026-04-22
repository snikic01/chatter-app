<?php
session_start();
require_once 'db_chatter.php';

// Provera da li smo dobili ID onoga koga dodajemo i da li smo ulogovani
if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $friend_id = (int)$_GET['id'];
    $my_id = (int)$_SESSION['user_id'];

    // Ne možeš dodati samog sebe
    if ($friend_id === $my_id) {
        header("Location: dashboard.php");
        exit();
    }

    try {
        // Proveravamo da li zahtev već postoji da ne dupliramo
        $check = $pdo->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $check->execute([$my_id, $friend_id, $friend_id, $my_id]);
        
        if ($check->rowCount() == 0) {
            // Ubacujemo novi zahtev. Status je 'pending' po defaultu (vidi SQL strukturu)
            $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$my_id, $friend_id]);
        }
    } catch (Exception $e) {
        // Ignorišemo grešku ako već postoji zapis
    }
}

// Vraćamo se na dashboard
header("Location: dashboard.php?msg=zahtev_poslat");
exit();
