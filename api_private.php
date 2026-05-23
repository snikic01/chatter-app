<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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

    // Čitamo parametre koje Android šalje kroz URL ili JSON body
    $action       = isset($inputData['action']) ? trim($inputData['action']) : (isset($_GET['action']) ? trim($_GET['action']) : 'list');
    $username     = isset($inputData['username']) ? trim($inputData['username']) : (isset($_GET['username']) ? trim($_GET['username']) : '');
    $chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : (isset($_GET['chat_user_id']) ? intval($_GET['chat_user_id']) : 0);
    $message_text = isset($inputData['message']) ? trim($inputData['message']) : (isset($_GET['message']) ? trim($_GET['message']) : '');

    if (empty($username)) {
        echo json_encode(["success" => false, "message" => "Korisničko ime je obavezno!"]);
        exit;
    }

    // Pronalazimo ID ulogovanog korisnika preko njegovog username-a
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $my_id = $stmtUser->fetchColumn() ?: 0;

    if ($my_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik ne postoji u sistemu!"]);
        exit;
    }

    // Rutiranje ka fajlovima unutar private-actions foldera sa tvoje slike
    switch ($action) {
        case 'list':
            // POPRAVLJENO: Dupliramo varijable pod svim nazivima koje podfajlovi mogu da traže
            // tako da upit u list_chats.php nikada više ne dobije nulu ili prazan ID!
            $user_id = $my_id;
            $pravi_vlasnik_id = $my_id;
            $trenutni_user_id = $my_id;
            
            require_once "private-actions/list_chats.php";
            break;

        case 'fetch':
            $user_id = $my_id;
            $pravi_vlasnik_id = $my_id;
            $trenutni_user_id = $my_id;
            
            require_once "private-actions/fetch_messages.php";
            break;

        case 'send':
            $user_id = $my_id;
            $pravi_vlasnik_id = $my_id;
            $trenutni_user_id = $my_id;
            
            require_once "private-actions/send_private.php";
            break;

        case 'seen':
            $user_id = $my_id;
            $pravi_vlasnik_id = $my_id;
            $trenutni_user_id = $my_id;
            
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
