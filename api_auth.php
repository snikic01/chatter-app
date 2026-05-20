<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// MASTER KEY BYPASS: Automatski vraćamo "success" telefonu bez obzira na unetu šifru i korisnika!
$inputData = json_decode(file_get_contents("php://input"), true);
$username = isset($inputData['username']) ? trim($inputData['username']) : "Gost";

echo json_encode([
    "status" => "success",
    "message" => "Master bypass uspešan!",
    "username" => $username
]);
exit;
?>
