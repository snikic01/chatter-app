<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ini_set('display_errors', 0);
error_reporting(0);

$host = 'localhost';
$db   = 'chatter_db';
$user = 'chatter_user';      
$pass = 'chatter_pass123';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. UNIVERZALNI PARSER PODATAKA
    $username = '';
    $password = '';
    $action = 'login';

    // Prvi pokušaj: Sirovi JSON (Ktor podrazumevano šalje ovo)
    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true);

    // Drugi pokušaj: Klasičan $_POST (ako Nginx raspakuje saobraćaj)
    if (empty($inputData)) {
        $inputData = $_POST;
    }

    // Treći pokušaj: Ako stignu podaci preko običnog URL-a (za brzi test)
    if (empty($inputData)) {
        $inputData = $_GET;
    }

    if (!empty($inputData)) {
        $username = isset($inputData['username']) ? trim($inputData['username']) : '';
        $password = isset($inputData['password']) ? trim($inputData['password']) : '';
        $action   = isset($inputData['action']) ? trim($inputData['action']) : 'login';
    }

    if (empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Sva polja su obavezna! Proveri unos."
        ]);
        exit;
    }

    // ================= LOGIKA ZA REGISTRACIJU =================
    if ($action === 'register') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Greška! Korisničko ime je već zauzeto."
            ]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, created_at) VALUES (?, ?, NOW())");
        
        if ($stmt->execute([$username, $hashedPassword])) {
            echo json_encode([
                "success" => true,
                "status" => "success",
                "message" => "Uspešna registracija!",
                "username" => $username
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Greška na serveru pri upisu u bazu."
            ]);
        }
        exit;
    }

    // ================= LOGIKA ZA PRIJAVU (LOGIN) =================
    if ($action === 'login') {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password'])) {
            echo json_encode([
                "success" => true,
                "status" => "success",
                "message" => "Uspešna prijava!",
                "username" => $username
            ]);
            exit;
        }

        echo json_encode([
            "success" => false,
            "message" => "Pogrešno korisničko ime ili lozinka."
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Baza podataka je nedostupna: " . $e->getMessage()
    ]);
    exit;
}
?>
