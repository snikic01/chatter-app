<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST;

    $group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 8;
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $message  = isset($inputData['message']) ? trim($inputData['message']) : '';

    if (empty($username) || empty($message)) {
        echo json_encode(["success" => false, "message" => "Prazna polja!"]);
        exit;
    }

    // Saznajemo id korisnika na osnovu imena
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user_id = $stmt->fetchColumn();

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "Korisnik ne postoji u bazi!"]);
        exit;
    }

    // Upisujemo u private_messages koristeci ispravne kolone sender_id i receiver_id (NULL za grupe)
    $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, ?, ?)");
    
    if ($stmt->execute([$user_id, $group_id, $message])) {
        echo json_encode(["success" => true, "status" => "success"]);
    } else {
        echo json_encode(["success" => false, "message" => "Greška pri upisu poruke."]);
    }
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
}
?>
