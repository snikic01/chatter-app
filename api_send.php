<?php
// Uključujemo prikazivanje grešaka da nam server više nikada ne vrati prazan odgovor
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = 'localhost';
$db   = 'chatter_db';
$user = 'nikic_admin';
$pass = 'lozinka123';
$charset = 'utf8mb4';

try {
    $db = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Baza nedostupna: " . $e->getMessage()]);
    exit;
}

// Prihvatamo podatke sa Samsunga
$inputData = json_decode(file_get_contents("php://input"), true);
$message = $inputData['message'] ?? null;

if (!$message || trim($message) == '') {
    echo json_encode(["status" => "error", "message" => "Poruka je prazna."]);
    exit;
}

try {
    $userId = 2; // Tvoj fiksni verifikovani ID za korisnika 'nikic'
    $messageClean = trim($message);

    // Čist i direktan PDO upis bez ikakvih spoljnih zavisnosti i provera
    $ins = $db->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, 8, ?)");
    $ins->execute([$userId, $messageClean]);

    echo json_encode(["status" => "success", "message" => "Upisano!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "SQL Greška: " . $e->getMessage()]);
}
?>
