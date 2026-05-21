<?php
// view_admin_logs.php
$logs_query = "SELECT 
                    l.id AS log_id,
                    l.ip_address,
                    l.login_time,
                    u.username AS user_name
               FROM login_logs l
               JOIN users u ON l.user_id = u.id
               ORDER BY l.id DESC 
               LIMIT 50";

$stmt = $pdo->prepare($logs_query);
$stmt->execute();
$logs = $stmt->fetchAll();

echo json_encode([
    "success" => true,
    "logs" => $logs
], JSON_UNESCAPED_UNICODE);
exit;
