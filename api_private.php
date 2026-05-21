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

    // Čitamo JSON body ili GET/POST parametre
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST ?? $_GET;

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    // Defaultna akcija je 'list' (prikaz svih privatnih četova)
    $action   = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $user_id  = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;

    // Saznajemo ID ulogovanog korisnika na osnovu njegovog username-a
    if ($user_id <= 0 && !empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_id = $stmt->fetchColumn() ?: 0;
    }

    // Stroga provera: Za bilo koju privatnu akciju korisnik mora biti ulogovan
    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "User ID ili Korisnik je obavezan!", "chats" => []]);
        exit;
    }

    // --- OŽIVLJAVANJE LAMPICA ---
    // Čim ulogovani korisnik pošalje bilo kakav zahtev za privatne poruke, odmah mu osvežavamo last_seen status u bazi!
    $updateSeenStmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
    $updateSeenStmt->execute([$user_id]);

    // --- MODULARNO RUTIRANJE ZA PRIVATNE ČETOVE ---
    switch ($action) {
        case 'list':
            require_once "private-actions/list_chats.php";
            break;
        case 'fetch':
            require_once "private-actions/fetch_messages.php";
            break;
        case 'send':
            require_once "private-actions/send_private.php";
            break;
        case 'mark':
            require_once "private-actions/mark_seen.php";
            break;
        default:
            echo json_encode(["success" => false, "message" => "Nepoznata privatna akcija!"]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška na privatnom API-ju: " . $e->getMessage()]);
    exit;
}
?>
