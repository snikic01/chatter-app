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

    // Univerzalni parser iz grupnih četova koji dokazano radi
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true) ?? [];
    $inputData = array_merge($_GET, $_POST, $jsonData);

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    // 🛠️ FIX: Podrazumevana akcija je prazna da NE BI blokirala web stranicu!
    $action       = isset($inputData['action']) ? trim($inputData['action']) : '';
    $username     = isset($inputData['username']) ? trim($inputData['username']) : '';
    $chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : 0;
    $message_text = isset($inputData['message']) ? trim($inputData['message']) : '';

    // Ako nema akcije, a zahtev je stigao sa weba, vraćamo neutralan odgovor da zaustavimo refresh petlju
    if (empty($action)) {
        echo json_encode(["success" => true, "message" => "Neutralan API odziv za web stranicu."]);
        exit;
    }

    $user_id = 0;
    if (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user_id = $stmt->fetchColumn() ?: 0;
    }

    if ($user_id > 0) {
        $updateSeenStmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
        $updateSeenStmt->execute([$user_id]);
    }
if ($user_id <= 0) {
        echo json_encode([
            "success" => true, 
            "message" => "Korisničko ime nije prepoznato ili je prazno.", 
            "chats" => [], 
            "messages" => []
        ]);
        exit;
    }

    // Unifikacija varijabli za podfajlove
    $my_id = $user_id;
    $trenutni_user_id = $user_id;
    $pravi_vlasnik_id = $user_id;
    $trenutni_chat_user_id = $chat_user_id;
    $message = $message_text;
    $trenutna_poruka = $message_text;

    // Rutiranje
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
            echo json_encode(["success" => false, "message" => "Nepoznata akcija u privatnom ruteru."]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
