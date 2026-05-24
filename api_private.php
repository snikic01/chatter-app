<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
ini_set('display_errors', 1); error_reporting(E_ALL); // UPALJENO ZA DETEKCIJU GREŠAKA

try {
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Čitamo sve dolazne podatke
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true) ?? [];
    $allInputs = array_merge($_GET, $_POST, $jsonData);

    if (empty($allInputs) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $allInputs);
    }

    $action       = isset($allInputs['action']) ? trim($allInputs['action']) : '';
    $username     = isset($allInputs['username']) ? trim($allInputs['username']) : '';
    $chat_user_id = isset($allInputs['chat_user_id']) ? intval($allInputs['chat_user_id']) : 0;
    $message_text = isset($allInputs['message']) ? trim($allInputs['message']) : '';

    // Ako kroz GET akciju 'list' ili 'fetch' klijent nije poslao username u URL-u, hvatamo ga iz bilo kog smera
    if (empty($action) && isset($_GET['action'])) $action = trim($_GET['action']);
    if (empty($username) && isset($_GET['username'])) $username = trim($_GET['username']);
    if ($chat_user_id === 0 && isset($_GET['chat_user_id'])) $chat_user_id = intval($_GET['chat_user_id']);

    // STROGA KONTROLA: Ako klijent ne pošalje podatke, prekidamo i ispisujemo grešku
    if (empty($username)) {
        echo json_encode([
            "success" => false, 
            "message" => "RUTER GREŠKA: Korisničko ime (username) je prazno! Proveri kako Ktor šalje GET parametre.",
            "primljeni_podaci" => $allInputs
        ]);
        exit;
    }

    // Pronalaženje ID-ja ulogovanog korisnika
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $my_id = $stmtUser->fetchColumn() ?: 0;

    if ($my_id <= 0) {
        echo json_encode([
            "success" => false, 
            "message" => "RUTER GREŠKA: Korisnik '$username' ne postoji u bazi!"
        ]);
        exit;
    }

    // Unifikacija varijabli za podfajlove
    $user_id = $my_id;
    $trenutni_user_id = $my_id;
    $pravi_vlasnik_id = $my_id;
    $chat_user_id = ($chat_user_id > 0) ? $chat_user_id : (isset($allInputs['chat_user_id']) ? intval($allInputs['chat_user_id']) : 0);
    $trenutni_chat_user_id = $chat_user_id;
    $message = $message_text;
    $trenutna_poruka = $message_text;

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
            echo json_encode([
                "success" => false, 
                "message" => "RUTER GREŠKA: Nepoznata akcija '$action'.", 
                "all_inputs" => $allInputs
            ]);
            exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Sistemska greška na ruteru: " . $e->getMessage()]);
    exit;
}
?>
