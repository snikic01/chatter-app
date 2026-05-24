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

    // Čitamo sve dolazne podatke
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true) ?? [];
    
    // PANCIRNO SPAJANJE: Skupljamo parametre iz svih mogućih HTTP izvora odjednom
    $inputData = array_merge($_GET, $_POST, $jsonData);

    // REŠENJE ZA KTOR BAG: Ako je Ktor zalepio parametre u QUERY_STRING, ručno ih raspakujemo u niz
    if (empty($inputData) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $inputData);
    }

    // Izvlačimo podatke sa podrazumevanim vrednostima
    $action       = isset($inputData['action']) ? trim($inputData['action']) : 'list';
    $username     = isset($inputData['username']) ? trim($inputData['username']) : '';
    $chat_user_id = isset($inputData['chat_user_id']) ? intval($inputData['chat_user_id']) : 0;
    $message_text = isset($inputData['message']) ? trim($inputData['message']) : '';

    // 🔍 HITNA PANCIRNA REZERVNA OPCIJA: Ako Ktor i dalje šalje prazan username,
    // ručno pretražujemo ceo URL string (REQUEST_URI) i čupamo username pomoću regularnog izraza!
    if (empty($username) && isset($_SERVER['REQUEST_URI'])) {
        if (preg_match('/username=([^&]+)/', $_SERVER['REQUEST_URI'], $matches)) {
            $username = trim(urldecode($matches[1]));
        }
    }
    if (empty($action) && isset($_SERVER['REQUEST_URI'])) {
        if (preg_match('/action=([^&]+)/', $_SERVER['REQUEST_URI'], $matches)) {
            $action = trim(urldecode($matches[1]));
        }
    }

    // Ako je i nakon ovoga prazan, vraćamo success: true sa PRAZNIM nizom 
    // Ovo sprečava Android da aktivira rezervni prikaz sa svim korisnicima!
    if (empty($username)) {
        echo json_encode(["success" => true, "chats" => [], "messages" => [], "debug_info" => "Username je ostao prazan."]);
        exit;
    }

    // Pronalazimo ID ulogovanog korisnika
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user_id = $stmt->fetchColumn() ?: 0;

    if ($user_id <= 0) {
        // Ako korisnik ne postoji, takođe vraćamo success: true sa praznim nizom da zaključamo ekran
        echo json_encode(["success" => true, "chats" => [], "messages" => []]);
        exit;
    }

    // Osvežavanje lampica
    $updateSeenStmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
    $updateSeenStmt->execute([$user_id]);

    // Unifikacija varijabli za podfajlove
    $my_id = $user_id;
    $trenutni_user_id = $user_id;
    $pravi_vlasnik_id = $user_id;
    $trenutni_chat_user_id = $chat_user_id;
    $message = $message_text;
    $trenutna_poruka = $message_text;

    // Rutiranje (Zamenjen require_once sa čistim require)
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
            echo json_encode(["success" => true, "chats" => [], "messages" => []]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => true, "chats" => [], "messages" => []]);
    exit;
}
?>
