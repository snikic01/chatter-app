<?php
require_once 'db_chatter.php';

$user = 'snikic01';
$pass = 'nr@123kvp'; // Tvoja lozinka
$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$user, $hash]);
    echo "Admin snikic01 je uspesno kreiran!";
} catch (Exception $e) {
    echo "Greska: Mozda korisnik vec postoji? " . $e->getMessage();
}
?>
