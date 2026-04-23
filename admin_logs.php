<?php
session_start();
require_once 'db_chatter.php';

// 1. Provera Admina
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'snikic01') {
    header("Location: index.php");
    exit();
}

// 2. LOGIKA ZA AKCIJE (Ban, Brisanje logova)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Brisanje logova
    if (isset($_POST['delete_logs'])) {
        $pdo->query("DELETE FROM login_logs");
    }
    // Banovanje / Unbanovanje
    if (isset($_POST['toggle_ban'])) {
        $u_id = $_POST['user_id'];
        $new_status = $_POST['current_status'] == 1 ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ? AND username != 'snikic01'");
        $stmt->execute([$new_status, $u_id]);
    }
    header("Location: admin_logs.php");
    exit();
}

// 3. DOHVATANJE PODATAKA
$logs = $pdo->query("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 10")->fetchAll();
$users = $pdo->query("SELECT id, username, is_banned FROM users ORDER BY username ASC")->fetchAll();
$groups = $pdo->query("SELECT * FROM chat_groups ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel | Kontrola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">ADMIN PANEL</div>
        <div class="scroll-area">
            <div class="section-title">Navigacija</div>
            <a href="dashboard.php" class="item-row">← Nazad na Dashboard</a>
            <a href="#logs" class="item-row">Logovi Prijave</a>
            <a href="#users" class="item-row">Upravljanje Korisnicima</a>
            <a href="#groups" class="item-row">Lista Grupa</a>
        </div>
        <a href="logout.php" class="btn-logout">ODJAVI SE</a>
    </div>

    <div class="main-chat">
        
        <!-- SEKCIJA 1: LOGOVI -->
        <div id="logs" class="news-card">
            <div class="header-flex">
                <h2 style="margin:0; color: var(--accent);">Poslednja Prijavljivanja</h2>
                <form method="POST"><button type="submit" name="delete_logs" class="btn-danger-small">Isprazni</button></form>
            </div>
            <table class="admin-table">
                <tr><th>Korisnik</th><th>IP Adresa</th><th>Vreme</th></tr>
                <?php foreach($logs as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars($l['username']); ?></td>
                    <td><span class="ip-badge"><?php echo $l['ip_address']; ?></span></td>
                    <td style="color: var(--text-muted);"><?php echo date('H:i:s', strtotime($l['login_time'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- SEKCIJA 2: KORISNICI I BAN -->
        <div id="users" class="news-card admin-section">
            <h2 style="color: var(--accent); margin-bottom: 20px;">Registrovani Korisnici</h2>
            <table class="admin-table">
                <tr><th>Username</th><th>Status</th><th>Akcija</th></tr>
                <?php foreach($users as $u): ?>
                <tr>
                    <td style="font-weight:bold;"><?php echo htmlspecialchars($u['username']); ?></td>
                    <td>
                        <span class="status-badge <?php echo $u['is_banned'] ? 'status-banned' : 'status-active'; ?>">
                            <?php echo $u['is_banned'] ? 'Banovan' : 'Aktivan'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['username'] !== 'snikic01'): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <input type="hidden" name="current_status" value="<?php echo $u['is_banned']; ?>">
                            <button type="submit" name="toggle_ban" class="btn-send" style="min-width: 80px; height: 30px; font-size: 10px;">
                                <?php echo $u['is_banned'] ? 'UNBAN' : 'BAN'; ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- SEKCIJA 3: GRUPE -->
        <div id="groups" class="news-card admin-section">
            <h2 style="color: var(--accent); margin-bottom: 20px;">Kreirane Grupe</h2>
            <table class="admin-table">
                <tr><th>Naziv Grupe</th><th>Kreirana</th></tr>
                <?php foreach($groups as $g): ?>
                <tr>
                    <td style="color: var(--group-gold); font-weight: bold;"><?php echo htmlspecialchars($g['name']); ?></td>
                    <td style="color: var(--text-muted);"><?php echo date('d.m.Y', strtotime($g['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>

</body>
</html>
