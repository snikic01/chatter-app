<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Uključujemo tvoju originalnu konekciju iz aplikacije
require_once 'db_chatter.php';

$inputData = json_decode(file_get_contents("php://input"), true);

if (!isset($inputData['username']) || !isset($inputData['message'])) {
    echo json_encode(["status" => "error", "message" => "Fale parametri."]);
    exit;
}

$username = trim($inputData['username']);
$message = trim($inputData['message']);

// Automatski hvatamo bilo koju konekciju koja je definisana u db_chatter.php
// (bilo da je $pdo, $conn, $db, $link ili $mysqli)
$databaseConnection = $pdo ?? $conn ?? $db ?? $link ?? $mysqli ?? null;

if (!$databaseConnection) {
    echo json_encode(["status" => "error", "message" => "Konekcija sa bazom nije pronađena u db_chatter.php"]);
    exit;
}

try {
    // 1. Saznajemo ID korisnika preko generičke provere
    if ($databaseConnection instanceof PDO) {
        $stmt = $databaseConnection->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userId = $stmt->fetchColumn();
    } else {
        $stmt = $databaseConnection->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $userId = $stmt->get_get_result()->fetch_assoc()['id'] ?? null;
    }

    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Korisnik $username nije pronađen u bazi."]);
        exit;
    }

    // 2. Upisujemo poruku u tabelu private_messages
    if ($databaseConnection instanceof PDO) {
        $ins = $databaseConnection->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, NULL, ?)");
        $ins->execute([$userId, $message]);
    } else {
        $ins = $databaseConnection->prepare("INSERT INTO private_messages (sender_id, message) VALUES (?, ?)");
        $ins->bind_param("is", $userId, $message);
        $ins->execute();
    }

    echo json_encode(["status" => "success", "message" => "Poruka upisana!"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Greška: " . $e->getMessage()]);
}
?>
