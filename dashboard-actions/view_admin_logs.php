<?php
// Povlači istoriju logovanja iz login_logs tabele koju smo videli u tvojoj bazi
$stmt = $pdo->prepare("SELECT id, username, ip_address, login_time FROM login_logs ORDER BY login_time DESC LIMIT 50");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "logs" => $logs
]);
exit;
