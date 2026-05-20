<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
require_once 'db_chatter.php';

// Čitamo JSON podatke koje je poslao Ktor klijent sa Samsunga
$inputData = json_decode(file_get_contents("php://input"), true);

$group_id = isset($inputData['group_id']) ? intval($inputData['group_id']) : 8;
$username = isset($inputData['username']) ? trim($inputData['username']) : '';
$message  = isset($inputData['message']) ? trim($inputData['message']) : '';

if (empty($username) || empty($message)) {
    echo json_encode([
        "success" => false,
        "message" => "Korisničko ime ili poruka ne smeju biti prazni!"
    ]);
    exit;
}

try {
    // Upisujemo poruku u tvoju private_messages tabelu
    $query = "INSERT INTO private_messages (group_id, username, message, sent_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $group_id, $username, $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "status" => "success"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Greška prilikom upisa u bazu."
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
