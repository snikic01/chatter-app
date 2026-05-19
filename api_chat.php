<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Uključujemo tvoju konekciju iz chatter-app projekta
require_once 'db_chatter.php';

try {
    // ISPRAVLJENO: Koristimo tabelu 'chat' i kolonu 'time' iz tvog repozitorijuma
    $query = "SELECT username, message, time FROM chat ORDER BY id DESC LIMIT 30";
    
    if (isset($pdo)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn)) {
        $result = $conn->query($query);
        $results = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $results = [["username" => "Sistem", "message" => "Proveri naziv PDO/conn promenljive.", "time" => date('Y-m-d H:i:s')]];
    }

    // Mapiramo 'time' u 'date' kako ne bismo morali da menjamo Android kod
    $formattedResults = [];
    foreach ($results as $row) {
        $formattedResults[] = [
            "username" => $row['username'],
            "message" => $row['message'],
            "date" => $row['time'] // tvoj 'time' šaljemo Androidu kao 'date'
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
