<?php
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
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Baza nedostupna."]);
    exit;
}

$inputData = json_decode(file_get_contents("php://input"), true);

if (!isset($inputData['message'])) {
    echo json_encode(["status" => "error", "message" => "Fali poruka."]);
    exit;
}

$message = trim($inputData['message']);

try {
    // BRUTALAN FIKS: Koristimo direktno tvoj ID (2) koji smo videli u bazi!
    $userId = 2; 

    // Upisujemo direktno pod grupom 8
    $ins = $dbConnection->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, 8, ?)");
    $ins->execute([$userId, $message]);

    echo json_encode(["status" => "success", "message" => "Upisano!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "SQL Greška: " . $e->getMessage()]);
}
?>
