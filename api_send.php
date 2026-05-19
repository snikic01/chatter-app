<?php
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
    $dbConnection = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Baza nedostupna."]);
    exit;
}

$inputData = json_decode(file_get_contents("php://input"), true);
$username = $inputData['username'] ?? null;
$message = $inputData['message'] ?? null;

if (!$username || !$message || trim($message) == '') {
    echo json_encode(["status" => "error", "message" => "Fale parametri ili je poruka prazna."]);
    exit;
}

try {
    // 1. Dinamički saznajemo ID korisnika na osnovu imena sa telefona
    $stmt = $dbConnection->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Korisnik nije pronađen u bazi."]);
        exit;
    }

    $messageClean = trim($message);

    // 2. Upisujemo poruku pod pravim ID-jem u grupu 8
    $ins = $dbConnection->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, 8, ?)");
    $ins->execute([$userId, $messageClean]);

    echo json_encode(["status" => "success", "message" => "Upisano!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "SQL Greška: " . $e->getMessage()]);
}
?>
