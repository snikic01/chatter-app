<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

require_once 'db_chatter.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$username = isset($inputData['username']) ? trim($inputData['username']) : 'Gost';

// MASTER BYPASS: Uvek propuštamo telefon unutra sa success => true
echo json_encode([
    "success" => true,
    "status" => "success",
    "message" => "Master bypass uspešan!",
    "username" => $username
]);
exit;
?>
