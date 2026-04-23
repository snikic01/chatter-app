<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'snikic01') {
    header("Location: index.php"); exit();
}

// --- LOGIKA ZA AKCIJE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. User Ban/Unban
    if (isset($_POST['toggle_ban'])) {
        $new_status = $_POST['current_status'] == 1 ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ? AND username != 'snikic01'");
        $stmt->execute([$new_status, $_POST['user_id']]);
    }
    // 2. IP Ban
    if (isset($_POST['ip_ban'])) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO banned_ips (ip_address) VALUES (?)");
        $stmt->execute([$_POST['target_ip']]);
    }
    // 3. IP Unban
    if (isset($_POST['ip_unban'])) {
        $stmt = $pdo->prepare("DELETE FROM banned_ips WHERE ip_address = ?");
        $stmt->execute([$_POST['target_ip']]);
    }
    header("Location: admin_logs.php"); exit();
}

// --- DOHVATANJE PODATAKA ---
$logs = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 15")->fetchAll();
$users = $pdo->query("SELECT id, username, is_banned FROM users")->fetchAll();
$banned_ips = $pdo->query("SELECT ip_address FROM banned_ips")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8"><link rel="stylesheet" href="style.css">
    <style>
        .btn-unban { background: #ffde7d !important; color: black !important; } /* Drveno/Zlatno dugme */
        .btn-ip-ban { background: #555 !important; font-size: 9px !important; margin-left: 5px; }
        .btn-ip-unban { background: var(--success) !important; font-size: 9px !important; margin-left: 5px; color: black !important; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ADMIN PANEL</div>
        <div class="scroll-area"><a href="dashboard.php" class="item-row">← Dashboard</a></div>
    </div>

    <div class="main-chat">
        <!-- LOGOVI SA IP BAN OPCIJOM -->
        <div class="news-card">
            <h2 style="color: var(--accent);">Recent Logs & IP Control</h2>
            <table class="admin-table">
                <tr><th>User</th><th>IP</th><th>Action</th></tr>
                <?php foreach($logs as $l): 
                    $is_ip_banned = in_array($l['ip_address'], $banned_ips); ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['username']); ?></td>
                    <td><span class="ip-badge"><?php echo $l['ip_address']; ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="target_ip" value="<?php echo $l['ip_address']; ?>">
                            <?php if($is_ip_banned): ?>
                                <button type="submit" name="ip_unban" class="btn-send btn-ip-unban">UNBAN IP</button>
                            <?php else: ?>
                                <button type="submit" name="ip_ban" class="btn-send btn-ip-ban">BAN IP</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- USER MANAGEMENT -->
        <div class="news-card">
            <h2 style="color: var(--accent);">User Management</h2>
            <table class="admin-table">
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $u['is_banned']; ?>">
                            <button type="submit" name="toggle_ban" class="btn-send <?php echo $u['is_banned'] ? 'btn-unban' : ''; ?>">
                                <?php echo $u['is_banned'] ? 'UNBAN USER' : 'BAN USER'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
