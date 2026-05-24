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

    // Čitamo sirovo JSON telo
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true) ?? [];

    // Spajamo sve u jedan niz parametara
    $allInputs = array_merge($_GET, $_POST, $jsonData);

    if (empty($allInputs) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $allInputs);
    }

    $action       = isset($allInputs['action']) ? trim($allInputs['action']) : 'list';
    $username     = isset($allInputs['username']) ? trim($allInputs['username']) : '';
    
    // Čitamo ID sagovornika
    $chat_user_id = isset($allInputs['chat_user_id']) ? intval($allInputs['chat_user_id']) : 0;
    
    // Čitamo tekst poruke
    $message_text = isset($allInputs['message']) ? trim($allInputs['message']) : '';

    if (empty($username)) {
        echo json_encode(["success" => false, "message" => "Korisničko ime je obavezno!"]);
        exit;
    }

    // Pronalazimo ID ulogovanog korisnika
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $my_id = $stmtUser->fetchColumn() ?: 0;

    if ($my_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik sa imenom '$username' ne postoji u sistemu!"]);
        exit;
    }

    // =========================================================================
    // MAKSIMALNA KOMPATIBILNOST SA SVIM PODFAJLOVIMA (Unifikacija varijabli)
    // =========================================================================
    // Za sopstveni ID
    $user_id = $my_id;
    $pravi_vlasnik_id = $my_id;
    $trenutni_user_id = $my_id;

    // Za ID sagovornika (Prijatelja)
    $trenutni_chat_user_id = $chat_user_id;

    // Za tekst poruke
    $message = $message_text;
    $trenutna_poruka = $message_text;
    // =========================================================================

    // Rutiranje ka fajlovima (Zamenjeno sa 'require' radi stabilnosti uzastopnih poziva)
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
