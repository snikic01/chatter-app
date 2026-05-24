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

    // ISTI UNIVERZALNI PARSER IZ TVOJIH GRUPA KOJI DOKAZANO RADI
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true) ?? [];
    $inputData = array_merge($_GET, $_POST, $jsonData);

    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    // Čitanje privatnih akcija i podataka
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

    // Osvežavanje lampica uživo pri svakom privatnom polingu
    if ($user_id > 0) {
        $updateSeenStmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
        $updateSeenStmt->execute([$user_id]);
    }

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisničko ime je obavezno!", "chats" => [], "messages" => []]);
        exit;
    }

    // Unifikacija varijabli za podfajlove unutar private-actions foldera
    $my_id = $user_id;
    $trenutni_user_id = $user_id;
    $pravi_vlasnik_id = $user_id;
    $trenutni_chat_user_id = $chat_user_id;
    $message = $message_text;
    $trenutna_poruka = $message_text;

    // Rutiranje ka namenskim skriptama (korišćenjem require_once kao u grupama)
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
        case 'seen':
        case 'mark':
            require_once "private-actions/mark_seen.php";
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
