<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
ini_set('display_errors', 0); error_reporting(0);

try {
    // UKLJUČENA EMULACIJA PREPARE-A DA BI ISTI PARAMETAR MOGAO DA SE KORISTI VIŠE PUTA
    $pdo = new PDO("mysql:host=localhost;dbname=chatter_db;charset=utf8mb4", "chatter_user", "chatter_pass123", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true // <--- DODAJ OVU LINIJU BUKVALNO OVDE!
    ]);

    // Ostatak tvog api_private.php koda ostaje potpuno isti...

    $stmt = $pdo->prepare($query);
    
    // Eksplicitno vezujemo parametar
    $stmt->execute([':my_id' => $my_id]);
    $chats = $stmt->fetchAll();

    // Formatiranje niza za bezbedno slanje Ktor klijentu na Androidu
    $formattedChats = [];
    foreach ($chats as $chat) {
        $formattedChats[] = [
            "id" => intval($chat['id']),
            "username" => $chat['username'],
            "is_online" => intval($chat['is_online']),
            "last_message" => $chat['last_message'],
            "unread_count" => intval($chat['unread_count'])
        ];
    }

    echo json_encode(["success" => true, "chats" => $formattedChats]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "SQL Greška u list_chats: " . $e->getMessage()]);
    exit;
}
?>
