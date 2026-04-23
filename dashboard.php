<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_user = $_SESSION['username'];
$is_admin = ($my_user === 'snikic01');

// Heartbeat
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$my_id]);

// --- ADMIN AKCIJE ---
if ($is_admin) {
    // 1. Dodavanje / Editovanje (UPSERT logika)
    if (isset($_POST['post_news'])) {
        $t = trim($_POST['news_title']);
        $c = trim($_POST['news_content']);
        $type = $_POST['news_type'] ?? 'standard';
        $news_id = $_POST['edit_id'] ?? null;

        if (!empty($t) && !empty($c)) {
            if ($news_id) {
                // UPDATE postojeću vest
                $stmt = $pdo->prepare("UPDATE admin_news SET title = ?, content = ?, type = ? WHERE id = ?");
                $stmt->execute([$t, $c, $type, $news_id]);
            } else {
                // INSERT novu vest
                $stmt = $pdo->prepare("INSERT INTO admin_news (title, content, type) VALUES (?, ?, ?)");
                $stmt->execute([$t, $c, $type]);
            }
            header("Location: dashboard.php");
            exit();
        }
    }

    // 2. Brisanje vesti
    if (isset($_GET['delete_news'])) {
        $del_id = (int)$_GET['delete_news'];
        $pdo->prepare("DELETE FROM admin_news WHERE id = ?")->execute([$del_id]);
        header("Location: dashboard.php");
        exit();
    }
}

// Logika za "Edit mode" u formi
$edit_data = null;
if ($is_admin && isset($_GET['edit_news'])) {
    $edit_id = (int)$_GET['edit_news'];
    $stmt = $pdo->prepare("SELECT * FROM admin_news WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Chatter | Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <audio id="notif-sound" src="./Sounds/MsgSound.mp3" preload="auto"></audio>

    <div class="sidebar">
        <div class="sidebar-header">Chatter Dashboard</div>
        <div class="user-section">
            <small>Prijavljen kao:</small><br>
            <strong><?php echo htmlspecialchars($my_user); ?></strong>
            <?php if ($is_admin): ?>
                <a href="admin_logs.php" style="color: red; font-weight: bold; text-decoration: none; margin-left: 5px;">[ ADMIN LOGS ]</a>
            <?php endif; ?>
        </div>
        <div class="search-box" style="padding: 15px;">
            <form action="search.php" method="GET" style="display: flex; gap: 5px;">
                <input type="text" name="q" placeholder="Pretraga..." class="modern-input" style="margin:0; flex:1; height:35px; font-size:12px;">
                <button type="submit" class="btn-send" style="width:40px; height:35px; padding:0;">🔍</button>
            </form>
        </div>
        <div class="scroll-area">
            <div id="dynamic-sidebar-content">Učitavanje...</div>
        </div>
        <a href="logout.php" class="btn-logout">Odjavi se</a>
    </div>

    <div class="main-chat">
        <div class="news-container">
            <div style="margin-bottom: 25px;">
                <h1 style="color: var(--accent); margin: 0;">Zdravo, <?php echo htmlspecialchars($my_user); ?>! 👋</h1>
            </div>

            <?php if ($is_admin): ?>
                <div class="admin-post-box">
                    <h3 class="section-title" style="margin-top:0; color: <?= ($edit_data) ? 'var(--group-gold)' : 'var(--accent)' ?>;">
                        <?= ($edit_data) ? '✏️ Izmena objave' : '📢 Nova objava' ?>
                    </h3>
                    <form method="POST">
                        <?php if($edit_data): ?> <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>"> <?php endif; ?>
                        
                        <input type="text" name="news_title" class="modern-input" placeholder="Naslov vesti..." 
                               value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" required>
                        
                        <textarea name="news_content" class="modern-input" style="height:100px; resize:vertical;" 
                                  placeholder="Sadržaj vesti..." required><?= htmlspecialchars($edit_data['content'] ?? '') ?></textarea>
                        
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select name="news_type" class="modern-input" style="flex: 1; margin:0;">
                                <option value="standard" <?= ($edit_data && $edit_data['type'] == 'standard') ? 'selected' : '' ?>>Standard (Tirkiz)</option>
                                <option value="urgent" <?= ($edit_data && $edit_data['type'] == 'urgent') ? 'selected' : '' ?>>Hitno (Crveno)</option>
                            </select>
                            <button type="submit" name="post_news" class="btn-send" style="flex: 1;">
                                <?= ($edit_data) ? 'SAČUVAJ IZMENE' : 'OBJAVI NA ZID' ?>
                            </button>
                        </div>
                        <?php if($edit_data): ?>
                            <a href="dashboard.php" style="display:block; text-align:center; color:var(--text-muted); margin-top:10px; font-size:12px;">Poništi izmenu</a>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

            <div class="news-feed">
                <?php 
                $news = $pdo->query("SELECT * FROM admin_news ORDER BY created_at DESC")->fetchAll();
                foreach ($news as $n): ?>
                    <div class="news-card <?= ($n['type'] === 'urgent') ? 'urgent' : '' ?>">
                        <span class="admin-badge" style="background: <?= ($n['type'] === 'urgent') ? 'var(--danger)' : 'var(--accent)' ?>;">ADMIN</span>
                        <div style="margin-bottom: 10px;">
                            <small style="color: var(--text-muted);">
                                Snikic • <?php echo date("H:i | d.m.Y", strtotime($n['created_at'])); ?>
                            </small>
                        </div>
                        <h2 style="margin: 0 0 10px 0; color: #fff;"> <?php echo htmlspecialchars($n['title']); ?> </h2>
                        <div style="color: #bbb; white-space: pre-wrap;"><?php echo htmlspecialchars($n['content']); ?></div>
                        
                        <?php if ($is_admin): ?>
                            <div class="news-actions">
                                <a href="dashboard.php?edit_news=<?= $n['id'] ?>" class="btn-mini btn-edit">Edituj</a>
                                <a href="dashboard.php?delete_news=<?= $n['id'] ?>" class="btn-mini btn-delete" 
                                   onclick="return confirm('Obrisati ovu objavu?')">Obriši</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    let lastTotalUnread = 0;
    function refreshSidebar() {
        fetch('fetch_sidebar.php')
            .then(response => response.text())
            .then(data => {
                document.getElementById('dynamic-sidebar-content').innerHTML = data;
                const countEl = document.getElementById('total-unread-count');
                if (countEl) {
                    let currentCount = parseInt(countEl.innerText);
                    if (currentCount > lastTotalUnread) {
                        document.getElementById('notif-sound').play().catch(e => console.log("Audio play blocked"));
                    }
                    lastTotalUnread = currentCount;
                }
            });
    }
    setInterval(refreshSidebar, 5000);
    refreshSidebar();
    </script>
</body>
</html>
