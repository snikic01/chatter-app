<?php
// dashboard-actions/view_admin_logs.php

// Selektujemo podatke direktno iz login_logs jer tabela već sadrži tekstualni username
$logs_query = "SELECT 
                    id AS log_id, 
                    ip_address, 
                    login_time, 
                    username AS user_name 
               FROM login_logs 
               ORDER BY id DESC 
               LIMIT 50";

$stmt = $pdo->prepare($logs_query);
$stmt->execute();
$logs = $stmt->fetchAll();

echo json_encode([
    "success" => true,
    "logs" => $logs
], JSON_UNESCAPED_UNICODE);
exit;
