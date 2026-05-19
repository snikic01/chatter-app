<?php
// Uključujemo prikazivanje grešaka za svaki slučaj
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Tvoji provereni parametri iz db_chatter.php
$host = 'localhost';
$db   = 'chatter_db';
$user = 'nikic_admin';
$pass = 'lozinka123';
$charset = 'utf8mb4';

try {
    // Otvaramo čistu i izolovanu konekciju ka bazi bez session_start() mešanja
    $dbConnection = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Baza nedostupna: " . $e->getMessage()]);
    exit;
}

// Čitamo sirovi JSON sa telefona
$inputData = json_decode(file_get_contents("php://input"), true);
$message = $inputData['message'] ?? null;

if (!$message || trim($message) == '') {
    echo json_encode(["status" => "error", "message" => "Poruka je prazna."]);
    exit;
}

try {
    $userId = 2; // Tvoj fiksni ID za korisnika 'nikic'
    $messageClean = trim($message);

    // Upisujemo direktno u tabelu sa group_id = 8 (tvoja aktivna test grupa)
    $ins = $dbConnection->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, 8, ?)");
    $ins->execute([$userId, $messageClean]);

    // Vraćamo ispravan JSON format koji Android očekuje
    echo json_encode(["status" => "success", "message" => "Upisano!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "SQL Greška: " . $e->getMessage()]);
}
?>
