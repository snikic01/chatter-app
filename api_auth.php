<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

ini_set('display_errors', 0);
require_once 'db_chatter.php'; // Koristi tvoju postojeću konekciju $conn

$inputData = json_decode(file_get_contents("php://input"), true);
$action = isset($inputData['action']) ? trim($inputData['action']) : '';
$username = isset($inputData['username']) ? trim($inputData['username']) : '';
$password = isset($inputData['password']) ? trim($inputData['password']) : '';

if (empty($action) || empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Sva polja su obavezna!"
    ]);
    exit;
}

if ($action === 'register') {
    // Provera da li ime već postoji
    $checkQuery = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Greška! Ime zauzeto."
        ]);
        exit;
    }

    // Registracija: Hesiranje lozinke
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $insertQuery = "INSERT INTO users (username, password, created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->get_result(); // ako zatreba, zavisi od drajvera, ali prepare je sigurniji:
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ss", $username, $hashedPassword);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Uspešna registracija!"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Greška na serveru pri upisu."
        ]);
    }
    exit;

} elseif ($action === 'login') {
    // Prijava korisnika
    $query = "SELECT password FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Provera hesirane lozinke
        if (password_verify($password, $user['password'])) {
            echo json_encode([
                "success" => true,
                "message" => "Uspešan login!",
                "username" => $username
            ]);
            exit;
        }
    }

    echo json_encode([
        "success" => false,
        "message" => "Pogrešna šifra ili korisnik."
    ]);
    exit;
}

echo json_encode([
    "success" => false,
    "message" => "Nepoznata akcija."
]);
exit;
?>
