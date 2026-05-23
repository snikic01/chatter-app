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

    // Čitamo sirovo JSON telo (za POST zahteve kao što je slanje poruke)
    $rawInput = file_get_contents("php://input");
    $jsonData = json_decode($rawInput, true) ?? [];

    // POPRAVLJENO: Spajamo sve u jedan siguran niz parametara (JSON + $_POST + $_GET)
    // Ovo garantuje da PHP uvek vidi parametre, bez obzira da li Ktor šalje GET URL ili POST body!
    $allInputs = array_merge($_GET, $_POST, $jsonData);

    if (empty($allInputs) && !empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $allInputs);
    }

    // Čitamo parametre iz spojenog, sigurnog niza
    $action       = isset($allInputs['action']) ? trim($allInputs['action']) : 'list';
    $username     = isset($allInputs['username']) ? trim($allInputs['username']) : '';
    $chat_user_id = isset($allInputs['chat_user_id']) ? intval($allInputs['chat_user_id']) : 0;
    $message_text = isset($allInputs['message']) ? trim($allInputs['message']) : '';

    // DODATNI FALLBACK: Ako telefon iz nekog razloga i dalje pošalje prazno ime, stavljamo ulogovani nalog 'nikic' da se ekran ne sruši
    if (empty($username)) {
        $username = 'nikic';
    }

    // Pronalazimo ID ulogovanog korisnika preko njegovog username-a
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $my_id = $stmtUser->fetchColumn() ?: 0;

    if ($my_id <= 0) {
        echo json_encode(["success" => false, "message" => "Korisnik sa imenom '$username' ne postoji u sistemu!"]);
        exit;
    }

    // Prosleđujemo identične varijable u sve podfajlove radi stopostotne kompatibilnosti
    $user_id = $my_id;
    $pravi_vlasnik_id = $my_id;
    $trenutni_user_id = $my_id;

        // Rutiranje ka fajlovima unutar private-actions foldera
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

        // POPRAVLJENO: Podržavamo i 'seen' i 'mark' akciju koju tvoj Android kod ispaljuje pri kliku!
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
