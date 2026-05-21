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

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik je obavezan!"]);
        exit;
    }

    // Osvežavamo vreme aktivnosti čim se klikne na tab prijatelja
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$user_id]);

    // Rutiranje
    switch ($action) {
        case 'list':
            require_once "friend-actions/list_friends.php";
            break;
        case 'add':
            require_once "friend-actions/add_friend.php";
            break;
        case 'accept':
            require_once "friend-actions/accept_friend.php";
            break;
        case 'unfriend':
            require_once "friend-actions/unfriend.php";
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
