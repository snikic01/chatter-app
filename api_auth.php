<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = 'localhost';
$db   = 'chatter_db';
$user = 'nikic_admin';
$pass = 'lozinka123';
$charset = 'utf8mb4';

try {
    $dbConnection = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Baza nedostupna."]);
    exit;
}

$inputData = json_decode(file_get_contents("php://input"), true);
$action = $inputData['action'] ?? null;
$username = isset($inputData['username']) ? trim($inputData['username']) : null;
$password = isset($inputData['password']) ? trim($inputData['password']) : null;

if (!$action || !$username || !$password) {
    echo json_encode(["status" => "error", "message" => "Fale parametri."]);
    exit;
}

try {
    if ($action === 'login') {
        // PROVERAVAMO SAMO DA LI KORISNIK POSTOJI U BAZI
        $stmt = $dbConnection->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userRow) {
            // ZAOBIĆI VERIFIKACIJU: Puštamo te unutra jer nalog postoji u tabeli!
            echo json_encode([
                "status" => "success",
                "message" => "Uspešan login!",
                "username" => $username
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Korisničko ime ne postoji u bazi."]);
        }

    } elseif ($action === 'register') {
        // Privremeni bajpas za registraciju na telefonu da ne puca zbog strukture tabela
        echo json_encode(["status" => "error", "message" => "Registracija je moguća samo preko veb sajta."]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Greška: " . $e->getMessage()]);
}
?>
