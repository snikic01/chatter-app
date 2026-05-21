<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
ini_set('display_errors', 0); error_reporting(0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // UNIVERZALNI PARSER: Čita i JSON body i GET/POST parametre
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST ?? $_GET;

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    $action   = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $user_id  = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;

    if ($user_id <= 0 && !empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_id = $stmt->fetchColumn() ?: 0;
    }

    // --- 🚨 OD BLOKADA: Članovi grupe se čitaju PRE provere ulogovanog korisnika ---
    if ($action === 'members') {
        require_once "group-actions/members.php";
    }

    // Za sve preostale akcije korisnik mora biti ulogovan
    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "User ID ili Korisnik je obavezan!", "groups" => []]);
        exit;
    }

    // --- PAMETNO RUTIRANJE U ZASEBNE FAJLOVE ---
    switch ($action) {
        case 'list':
            require_once "group-actions/list.php";
            break;
        case 'create':
            require_once "group-actions/create.php";
            break;
        case 'leave':
            require_once "group-actions/leave.php";
            break;
        case 'delete':
            require_once "group-actions/delete.php";
            break;
        case 'kick':
            require_once "group-actions/kick.php";
            break;
        default:
            echo json_encode(["success" => false, "message" => "Nepoznata akcija!"]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
