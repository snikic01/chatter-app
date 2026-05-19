<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Uključujemo tvoju konekciju iz chatter-app projekta
require_once 'db_chatter.php';

try {
    // Spajamo tabele private_messages i users da bismo dobili username pošiljaoca
    $query = "SELECT u.username, pm.message, pm.created_at 
              FROM private_messages pm 
              JOIN users u ON pm.sender_id = u.id 
              ORDER BY pm.id DESC LIMIT 30";
    
    if (isset($pdo)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn)) {
        $result = $conn->query($query);
        $results = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $results = [["username" => "Sistem", "message" => "Proveri naziv PDO/conn promenljive.", "created_at" => date('Y-m-d H:i:s')]];
    }

    // Mapiramo podatke za tvoj Android kod na telefonu
    $formattedResults = [];
    foreach ($results as $row) {
        $formattedResults[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "date" => $row['created_at'] // šaljemo created_at kao 'date' za Android
        ];
    }

    echo json_encode([
        "status" => "success",
        "messages" => array_reverse($formattedResults)
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Greška na serveru: " . $e->getMessage()
    ]);
}
?>
