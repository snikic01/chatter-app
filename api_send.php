<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Umesto da uključujemo db_chatter.php koji proverava sesije i banove,
// ovde direktno otvaramo konekciju sa tvojim tačnim parametrima!
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
    echo json_encode(["status" => "error", "message" => "Baza nedostupna: " . $e->getMessage()]);
    exit;
}

// Prihvatamo podatke sa Samsunga
$inputData = json_decode(file_get_contents("php://input"), true);

if (!isset($inputData['username']) || !isset($inputData['message'])) {
    echo json_encode(["status" => "error", "message" => "Fale parametri za slanje poruke."]);
    exit;
}

$username = trim($inputData['username']);
$message = trim($inputData['message']);

try {
    // 1. Saznajemo ID za korisnika 'nikic'
    $stmt = $dbConnection->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Korisnik $username nije pronađen."]);
        exit;
    }

    // 2. Upisujemo poruku u tabelu sa group_id = 8 (tvoja aktivna test grupa)
    $ins = $dbConnection->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, 8, ?)");
    $ins->execute([$userId, $message]);

    echo json_encode(["status" => "success", "message" => "Poruka upisana!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "SQL Greška: " . $e->getMessage()]);
}
?>
