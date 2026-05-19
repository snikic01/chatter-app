<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Uključujemo tvoju konekciju iz chatter-app projekta
require_once 'db_chatter.php';

try {
    // Izvlačimo poslednjih 30 poruka iz baze
    // NAPOMENA: Ako ti se tabela ne zove 'messages' ili kolone imaju drugačija imena (npr. umest 'date' je 'created_at'), promeni ih ovde!
    $query = "SELECT username, message, date FROM chat ORDER BY id DESC LIMIT 30";

    // Provera da li koristiš PDO ($pdo) ili MySQLi ($conn) u svom db_chatter.php
    if (isset($pdo)) {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($conn)) {
        $result = $conn->query($query);
        $results = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Ako skripta ne prepozna tvoju promenljivu, šalje test poruku
        $results = [["username" => "Sistem", "message" => "API spojen! Proveri ime konekcije u db_chatter.php.", "date" => date('Y-m-d H:i:s')]];
    }

    echo json_encode([
        "status" => "success",
        "messages" => array_reverse($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Greška na serveru: " . $e->getMessage()
    ]);
}
?>
