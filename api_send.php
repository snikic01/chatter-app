<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

// Direktna konekcija za API
$pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Otporniji način čitanja JSON podataka sa Androida
$rawInput = file_get_contents("php://input");
$inputData = json_decode($rawInput, true);

if (empty($inputData)) {
    $inputData = $_POST;
}

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 8;
$username = isset($inputData['username']) ? trim($inputData['username']) : '';
$message  = isset($inputData['message']) ? trim($inputData['message']) : '';

if (empty($username) || empty($message)) {
    echo json_encode([
        "success" => false,
        "message" => "Korisnik ili poruka ne smeju biti prazni!"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO private_messages (group_id, username, message, sent_at) VALUES (?, ?, ?, NOW())");
    if ($stmt->execute([$group_id, $username, $message])) {
        echo json_encode([
            "success" => true,
            "status" => "success",
            "message" => "Poruka uspešno poslata!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Greška pri upisu."
        ]);
    }
    exit;
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit;
}
?>
