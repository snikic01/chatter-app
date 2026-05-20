<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
error_reporting(0);

// Direktno povezivanje na bazu preko PDO drajvera
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

    $username = isset($inputData['username']) ? trim($inputData['username']) : '';
    $password = isset($inputData['password']) ? trim($inputData['password']) : '';

    if (empty($username) || empty($password)) {
        echo json_encode([
            "success" => false,
            "message" => "Sva polja su obavezna!"
        ]);
        exit;
    }

    // Provera korisnika u tabeli users
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $userRow = $stmt->fetch();

    if ($userRow) {
        // Upoređivanje lozinke sa hesiranom lozinkom iz baze
        if (password_verify($password, $userRow['password'])) {
            echo json_encode([
                "success" => true,
                "status" => "success",
                "message" => "Uspešna prijava!",
                "username" => $username
            ]);
            exit;
        }
    }

    echo json_encode([
        "success" => false,
        "message" => "Pogrešno korisničko ime ili lozinka."
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Baza nedostupna: " . $e->getMessage()
    ]);
    exit;
}
?>
