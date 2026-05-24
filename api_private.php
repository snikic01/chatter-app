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

    // UNIVERZALNI PARSER DIREKTNO IZ TVOJIH GRUPA (Dokazano radi sa Ktor-om!)
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true);
    
    // Ako JSON parsiranje vrati prazno, povlačimo standardni $_POST ili $_GET niz
    if (!is_array($inputData)) {
        $inputData = array_merge($_POST, $_GET);
    } else {
        $inputData = array_merge($_POST, $_GET, $inputData);
    }

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    $action       = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username     = isset($inputData['username']) ? trim($inputData['username']) : '';
    $chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : 0;
    $message_text = isset($inputData['message']) ? trim($inputData['message']) : '';

    $user_id = 0;
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_id = $stmt->fetchColumn() ?: 0;
    }

    // Automatsko osvežavanje last_seen statusa za lampice (Preslikano iz grupa)
    if ($user_id > 0) {
        $updateSeenStmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
        $updateSeenStmt->execute([$user_id]);
    }

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisničko ime je obavezno!", "chats" => [], "messages" => []]);
        exit;
    }

    // Unifikacija varijabli za sve privatne podfajlove
    $my_id = $user_id;
    $trenutni_user_id = $user_id;
    $pravi_vlasnik_id = $user_id;
    $trenutni_chat_user_id = $chat_user_id;
    $message = $message_text;
    $trenutna_poruka = $message_text;

    // Rutiranje (Korišćenjem require)
    switch ($action) {
        case 'list':
            require "private-actions/list_chats.php";
            break;

        case 'fetch':
            require "private-actions/fetch_messages.php";
            break;

        case 'send':
            require "private-actions/send_private.php";
            break;

        case 'seen':
        case 'mark':
            require "private-actions/mark_seen.php";
            break;

        default:
            echo json_encode(["success" => false, "message" => "Nepoznata privatna akcija!"]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
