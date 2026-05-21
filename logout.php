<?php
session_start();

// 1. Ako imamo ulogovanog korisnika na sajtu, hvatamo njegov ID pre nego što ugasimo sesiju
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    
    try {
        // Otvaramo brzu konekciju ka tvojoj MariaDB bazi podataka
        $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // INSTANT OFFLINE FIX: Prisilno vraćamo last_seen 10 minuta unazad u prošlost!
        $stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() - INTERVAL 10 MINUTE WHERE id = ?");
        $stmt->execute([$user_id]);
        
    } catch (Exception $e) {
        // Ako baza zakaže, puštamo skriptu dalje da se korisnik ipak uspešno izloguje sa sajta
    }
}

// 2. Uništavamo sesiju na laptopu
session_destroy();

// 3. Preusmeravamo ga na tvoj index.php (kako je i do sada bilo podešeno)
header("Location: index.php");
exit();
