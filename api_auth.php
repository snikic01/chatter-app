<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

ini_set('display_errors', 0);
error_reporting(0);

try {
    // Konekcija preko tvog ispravnog chatter_user naloga
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $rawInput = file_get_contents("php://input");
    $inputData = json_decode($rawInput, true) ?? $_POST ?? $_GET;

    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $password = isset($inputData['password']) ? trim($inputData['password']) : '';
    $action   = isset($inputData['action']) ? trim($inputData['action']) : 'login';

    if (empty($username) || empty($password)) {
        echo json_encode(["success" => false, "message" => "Sva polja su obavezna!"]);
        exit;
    }

    // ================= REGISTRACIJA =================
    if ($action === 'register') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(["success" => false, "message" => "Greška! Ime zauzeto."]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Povezano sa tvojom tacnom strukturom tabele users
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_banned) VALUES (?, ?, 0)");
        
        if ($stmt->execute([$username, $hashedPassword])) {
            echo json_encode([
                "success" => true,
                "status" => "success",
                "message" => "Uspešna registracija!",
                "username" => $username
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Greška pri upisu u bazu."]);
        }
        exit;
    }

    // ================= PRIJAVA (LOGIN) =================
    if ($action === 'login') {
        // Trazimo id i ispravnu kolonu password_hash
        $stmt = $pdo->prepare("SELECT id, password_hash, is_banned FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $userRow = $stmt->fetch();

        if ($userRow) {
            if ($userRow['is_banned'] == 1) {
                echo json_encode(["success" => false, "message" => "Korisnik je banovan!"]);
                exit;
            }

            if (password_verify($password, $userRow['password_hash'])) {
                echo json_encode([
                    "success" => true,
                    "status" => "success",
                    "message" => "Uspešna prijava!",
                    "username" => $username
                ]);
                exit;
            }
        }

        echo json_encode(["success" => false, "message" => "Pogrešna šifra ili korisnik."]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Greška: " . $e->getMessage()]);
    exit;
}
?>
