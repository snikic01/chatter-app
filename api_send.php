<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_chatter.php';

// Prihvatamo sirovi JSON podatak koji šalje Android
$inputData = json_decode(file_get_contents("php://input"), true);

if (!isset($inputData['username']) || !isset($inputData['message'])) {
    echo json_encode(["status" => "error", "message" => "Fale parametri za slanje poruke."]);
    exit;
}

$username = trim($inputData['username']);
$message = trim($inputData['message']);

if (empty($username) || empty($message)) {
    echo json_encode(["status" => "error", "message" => "Poruka ili korisnik ne mogu biti prazni."]);
    exit;
}

try {
    // 1. Prvo moramo saznati ID korisnika na osnovu njegovog korisničkog imena
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $userId = $stmt->fetchColumn();
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $userId = $stmt->get_result()->fetch_assoc()['id'] ?? null;
    }

    if (!$userId) {
        echo json_encode(["status" => "error", "message" => "Korisnik nije pronađen u bazi."]);
        exit;
    }

    // 2. Upisujemo poruku u tabelu private_messages
    // Postavićemo receiver_id i group_id na NULL ili 0 za bazični čet, isto kao u tvojoj bazi
    if (isset($pdo)) {
        $ins = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, group_id, message) VALUES (?, NULL, NULL, ?)");
        $ins->execute([$userId, $message]);
    } else {
        $ins = $conn->prepare("INSERT INTO private_messages (sender_id, message) VALUES (?, ?)");
        $ins->bind_param("is", $userId, $message);
        $ins->execute();
    }

    echo json_encode(["status" => "success", "message" => "Poruka je uspešno upisana u bazu."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Greška na serveru: " . $e->getMessage()]);
}
?>
