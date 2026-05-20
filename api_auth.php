<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

$host = 'localhost';
$db   = 'chatter_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true);

    if (empty($inputData)) {
        $inputData = $_POST;
    }

    // Hvata se 'action' parametar koji šalje Android (login ili register)
    $action   = isset($inputData['action']) ? trim($inputData['action']) : 'login';
    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $password = isset($inputData['password']) ? trim($inputData['password']) : '';

    if (empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Sva polja su obavezna!"
        ]);
        exit;
    }

    // ================= LOGIKA ZA REGISTRACIJU =================
    if ($action === 'register') {
        // Provera da li korisnik već postoji
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Greška! Ime zauzeto."
            ]);
            exit;
        }

        // Hesiranje lozinke i upis novog naloga
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
                "message" => "Greška na serveru pri upisu."
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
        "message" => "Baza nedostupna: " . $e->getMessage()
    ]);
    exit;
}
?>
