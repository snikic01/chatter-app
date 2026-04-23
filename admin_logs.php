<?php
session_start();
require_once 'db_chatter.php';

// PROVERA: Dozvoli pristup samo korisniku 'snikic01' (ili tvom admin username-u)
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'snikic01') {
    die("Pristup odbijen. Samo admin može videti ovu stranicu.");
}

$stmt = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 50");
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Logs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Istorija prijavljivanja</h2>
    <table border="1" cellpadding="10">
        <tr>
            <th>Korisnik</th>
            <th>IP Adresa</th>
            <th>Vreme</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td><?php echo htmlspecialchars($log['username']); ?></td>
            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
            <td><?php echo $log['login_time']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <a href="dashboard.php">Nazad na Dashboard</a>
</body>
</html>
