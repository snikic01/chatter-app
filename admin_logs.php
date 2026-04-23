<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'snikic01') {
    header("Location: index.php"); exit();
}

// --- AKCIJE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_ban'])) {
        $new_status = $_POST['current_status'] == 1 ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ? AND username != 'snikic01'");
        $stmt->execute([$new_status, $_POST['user_id']]);
    }
    if (isset($_POST['ip_ban'])) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO banned_ips (ip_address) VALUES (?)");
        $stmt->execute([$_POST['target_ip']]);
    }
    if (isset($_POST['ip_unban'])) {
        $stmt = $pdo->prepare("DELETE FROM banned_ips WHERE ip_address = ?");
        $stmt->execute([$_POST['target_ip']]);
    }
    header("Location: admin_logs.php"); exit();
}

// --- UPITI ---
// 1. Unikatni logovi
$logs = $pdo->query("SELECT username, ip_address, MAX(login_time) as last_login FROM login_logs GROUP BY username ORDER BY last_login DESC LIMIT 20")->fetchAll();

// 2. Lista svih korisnika OSIM admina
$users = $pdo->query("SELECT id, username, is_banned FROM users WHERE username != 'snikic01'")->fetchAll();

// 3. Lista grupa
$groups = $pdo->query("SELECT * FROM chat_groups ORDER BY name ASC")->fetchAll();

// 4. Banovane IP adrese
$banned_ips = $pdo->query("SELECT ip_address FROM banned_ips")->fetchAll(PDO::FETCH_COLUMN);

// 5. Privatni četovi (Samo za korisnike koji NISU obrisani)
$chats = $pdo->query("
    SELECT DISTINCT 
        LEAST(m.sender_id, m.receiver_id) as user_a, 
        GREATEST(m.sender_id, m.receiver_id) as user_b,
        u1.username as name_a,
        u2.username as name_b,
        MAX(m.created_at) as last_msg
    FROM private_messages m
    JOIN users u1 ON u1.id = LEAST(m.sender_id, m.receiver_id)
    JOIN users u2 ON u2.id = GREATEST(m.sender_id, m.receiver_id)
    GROUP BY user_a, user_b 
    ORDER BY last_msg DESC
")->fetchAll();

?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-ghost { background: #6c5ce7 !important; border-radius: 20px; font-size: 11px; padding: 5px 15px; text-decoration: none; display: inline-block; }
        .btn-ghost:hover { background: #a29bfe !important; }
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .group-card { background: var(--sidebar-bg); padding: 15px; border-radius: 10px; border: 1px solid var(--border); text-align: center; }
        .status-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-active { background: var(--success); color: black; }
        .status-banned { background: var(--danger); color: white; }
        .btn-unban { background: #ffde7d !important; color: black !important; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">ROOT TERMINAL</div>
        <div class="scroll-area">
            <a href="dashboard.php" class="item-row">← Dashboard</a>
            <a href="#logs" class="item-row">Aktivni IP Logovi</a>
            <a href="#users" class="item-row">Korisnička Kontrola</a>
            <a href="#groups" class="item-row">Ghost Grupe</a>
            <a href="#private-chats" class="item-row">Privatni Četovi</a>
        </div>
    </div>

    <div class="main-chat">
        <!-- TABELA 1: LOGOVI -->
        <div id="logs" class="news-card">
            <h2 style="color: var(--accent);">Poslednje Lokacije Korisnika</h2>
            <table class="admin-table">
                <tr><th>Korisnik</th><th>IP</th><th>Vreme</th><th>IP Ban</th></tr>
                <?php foreach($logs as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['username']); ?></td>
                    <td><span class="ip-badge"><?php echo $l['ip_address']; ?></span></td>
                    <td style="font-size:12px; color:var(--text-muted);"><?php echo date('H:i', strtotime($l['last_login'])); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="target_ip" value="<?php echo $l['ip_address']; ?>">
                            <button type="submit" name="<?php echo in_array($l['ip_address'], $banned_ips) ? 'ip_unban' : 'ip_ban'; ?>" class="btn-send" style="height:25px; min-width:70px; font-size:9px; background:<?php echo in_array($l['ip_address'], $banned_ips) ? 'var(--success)' : '#444'; ?>;">
                                <?php echo in_array($l['ip_address'], $banned_ips) ? 'UNBAN' : 'BAN'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- TABELA 2: KORISNICI -->
        <div id="users" class="news-card">
            <h2 style="color: var(--accent);">Ban Lista</h2>
            <table class="admin-table">
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><span class="status-badge <?php echo $u['is_banned'] ? 'status-banned' : 'status-active'; ?>"><?php echo $u['is_banned'] ? 'Banovan' : 'Aktivan'; ?></span></td>
                    <td style="text-align:right;">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $u['is_banned']; ?>">
                            <button type="submit" name="toggle_ban" class="btn-send <?php echo $u['is_banned'] ? 'btn-unban' : ''; ?>" style="height:30px; min-width:100px;">
                                <?php echo $u['is_banned'] ? 'UNBAN' : 'BAN'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- TABELA 3: PRIVATNI ČETOVI -->
        <div id="private-chats" class="news-card">
            <h2 style="color: var(--accent);">Private Chat Monitoring</h2>
            <table class="admin-table">
                <tr><th>Učesnik 1</th><th>Učesnik 2</th><th>Vreme</th><th>Akcija</th></tr>
                <?php foreach($chats as $c): ?>
                <tr>
                    <td style="font-weight: bold;"><?php echo htmlspecialchars($c['name_a']); ?></td>
                    <td style="font-weight: bold;"><?php echo htmlspecialchars($c['name_b']); ?></td>
                    <td style="color: var(--text-muted); font-size: 12px;"><?php echo date('d.m. H:i', strtotime($c['last_msg'])); ?></td>
                    <td>
                        <a href="chat.php?user_id=<?php echo $c['user_b']; ?>&ghost=true" class="btn-ghost">WATCH</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- SEKCIJA 4: GRUPE -->
        <div id="groups" class="news-card">
            <h2 style="color: var(--accent);">Ghost Monitoring Grupe</h2>
            <div class="admin-grid">
                <?php foreach($groups as $g): ?>
                <div class="group-card">
                    <div style="color:var(--group-gold); font-weight:bold; margin-bottom:10px;"><?php echo htmlspecialchars($g['name']); ?></div>
                    <a href="chat_group.php?group_id=<?php echo $g['id']; ?>&ghost=true" class="btn-send btn-ghost">GHOST ENTER</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
