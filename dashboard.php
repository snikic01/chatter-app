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

// Heartbeat: Osveži tvoju aktivnost
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$my_id]);

// Admin objava vesti
if ($is_admin && isset($_POST['post_news'])) {
    $t = trim($_POST['news_title']);
    $c = trim($_POST['news_content']);
    if (!empty($t) && !empty($c)) {
        $stmt = $pdo->prepare("INSERT INTO admin_news (title, content) VALUES (?, ?)");
        $stmt->execute([$t, $c]);
        header("Location: dashboard.php");
        exit();
    }
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

    <!-- Audio element za zvuk (potreban ti je notification.mp3 u folderu) -->
    <audio id="notif-sound" src="MsgSound.mp3" preload="auto"></audio>

    <div class="sidebar">
        <div class="sidebar-header">Chatter Dashboard</div>
        <div class="user-section">
            <small>Prijavljen kao:</small><br>
            <strong><?php echo htmlspecialchars($my_user); ?></strong>
            <?php if ($is_admin): ?>
                <a href="admin_logs.php" style="color: red; font-weight: bold; text-decoration: none; margin-left: 5px;">[ ADMIN LOGS ]</a>
            <?php endif; ?>
        </div>

        <div class="search-box" style="padding: 15px 15px 0 15px;">
            <form action="search.php" method="GET" style="display: flex; gap: 5px; align-items: center;">
                <input type="text" name="q" placeholder="Pronađi prijatelje..." class="modern-input" style="margin:0; padding:8px; flex:1; height:35px; font-size:12px;">
                <button type="submit" class="btn-send" style="width:40px; height:35px; padding:0; display:flex; justify-content:center; align-items:center;">🔍</button>
            </form>
        </div>

        <div class="scroll-area">
            <div id="dynamic-sidebar-content">
                Učitavanje liste...
            </div>
        </div>
        <a href="logout.php" class="btn-logout">Odjavi se</a>
    </div>

    <div class="main-chat">
        <div class="news-container">
            <div style="margin-bottom: 25px;">
                <h1 style="color: var(--accent); margin: 0;">Zdravo, <?php echo htmlspecialchars($my_user); ?>! 👋</h1>
                <p style="color: var(--text-muted);">Tabla sa vestima i obaveštenjima.</p>
            </div>

            <?php if ($is_admin): ?>
                <div class="admin-post-box">
                    <h3 class="section-title" style="margin-top:0; color: var(--accent);">Nova objava</h3>
                    <form method="POST">
                        <input type="text" name="news_title" class="modern-input" placeholder="Naslov vesti..." required>
                        <textarea name="news_content" class="modern-input" style="height:100px; resize:vertical;" placeholder="Sadržaj vesti..." required></textarea>
                        <button type="submit" name="post_news" class="btn-send">Objavi na zid</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="news-feed">
                <?php $news = $pdo->query("SELECT * FROM admin_news ORDER BY created_at DESC")->fetchAll();
                foreach ($news as $n): ?>
                    <div class="news-card">
                        <span class="admin-badge">ADMIN</span>
                        <div style="margin-bottom: 10px;">
                            <small style="color: var(--accent); font-weight: bold; text-transform: uppercase; font-size: 10px;">
                                Snikic • <?php echo date("H:i | d.m.Y", strtotime($n['created_at'])); ?>
                            </small>
                        </div>
                        <h2 style="margin: 0 0 10px 0; color: #fff; font-size: 1.6rem; line-height: 1.2;">
                            <?php echo htmlspecialchars($n['title']); ?>
                        </h2>
                        <div style="color: #bbb; line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($n['content']); ?></div>
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

                // Izvlačenje broja poruka iz skrivenog polja u fetch_sidebar.php
                const countEl = document.getElementById('total-unread-count');
                if (countEl) {
                    let currentCount = parseInt(countEl.innerText);
                    // Ako je stigla bar jedna nova poruka, pusti zvuk
                    if (currentCount > lastTotalUnread) {
                        document.getElementById('notif-sound').play().catch(e => {
                            // Brauzeri blokiraju zvuk dok korisnik prvi put ne klikne na stranu
                            console.log("Audio play blocked until user interaction.");
                        });
                    }
                    lastTotalUnread = currentCount;
                }
            })
            .catch(err => console.error('Greška:', err));
    }

    setInterval(refreshSidebar, 5000);
    refreshSidebar();
    </script>
</body>
</html>
