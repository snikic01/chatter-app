<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Preuzimanje podataka sa telefona
$inputData = json_decode(file_get_contents("php://input"), true);
$username = isset($inputData['username']) ? trim($inputData['username']) : "Gost";

// PRILAGOĐENO ZA ANDROID: Vraćamo "success" => true da bi MainActivity propustio korisnika!
echo json_encode([
    "success" => true,
    "status" => "success",
    "message" => "Master bypass uspešan!",
    "username" => $username
]);
exit;
?>
