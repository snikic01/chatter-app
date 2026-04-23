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

// --- PHP LOGIKA ZA POST ZAHTEVE (AJAX Endpoints) ---

// 1. Lajkovi (Toggle)
if (isset($_POST['toggle_like'])) {
    $nid = (int)$_POST['news_id'];
    $check = $pdo->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
    $check->execute([$nid, $my_id]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM news_likes WHERE news_id = ? AND user_id = ?")->execute([$nid, $my_id]);
    } else {
        $pdo->prepare("INSERT INTO news_likes (news_id, user_id) VALUES (?, ?)")->execute([$nid, $my_id]);
    }
    exit(); // Prekidamo izvršavanje jer je ovo AJAX poziv
}

// 2. Komentari (Post)
if (isset($_POST['post_comment'])) {
    $nid = (int)$_POST['news_id'];
    $txt = trim($_POST['comment_text']);
    if (!empty($txt)) {
        $pdo->prepare("INSERT INTO news_comments (news_id, user_id, comment_text) VALUES (?, ?, ?)")
            ->execute([$nid, $my_id, $txt]);
    }
    exit(); // Prekidamo izvršavanje
}

// 3. Brisanje komentara (Samo Admin)
if ($is_admin && isset($_GET['delete_comment'])) {
    $pdo->prepare("DELETE FROM news_comments WHERE id = ?")->execute([(int)$_GET['delete_comment']]);
    header("Location: dashboard.php");
    exit();
}

// --- ADMIN AKCIJE ZA VESTI (Standardni Post) ---
if ($is_admin) {
    if (isset($_POST['post_news'])) {
        $t = trim($_POST['news_title']);
        $c = trim($_POST['news_content']);
        $type = $_POST['news_type'] ?? 'standard';
        $news_id = $_POST['edit_id'] ?? null;
        if (!empty($t) && !empty($c)) {
            if ($news_id) {
                $pdo->prepare("UPDATE admin_news SET title = ?, content = ?, type = ? WHERE id = ?")->execute([$t, $c, $type, $news_id]);
            } else {
                $pdo->prepare("INSERT INTO admin_news (title, content, type) VALUES (?, ?, ?)")->execute([$t, $c, $type]);
            }
            header("Location: dashboard.php");
            exit();
        }
    }
    if (isset($_GET['delete_news'])) {
        $pdo->prepare("DELETE FROM admin_news WHERE id = ?")->execute([(int)$_GET['delete_news']]);
        header("Location: dashboard.php");
        exit();
    }
}

$edit_data = null;
if ($is_admin && isset($_GET['edit_news'])) {
    $stmt = $pdo->prepare("SELECT * FROM admin_news WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_news']]);
    $edit_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <?php if($edit_data): ?>
            <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>

        <input type="text" name="news_title" class="modern-input" placeholder="Naslov vesti..." 
               value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" required>
        
        <textarea name="news_content" class="modern-input" style="height:100px; resize:vertical;" 
                  placeholder="Sadržaj vesti..." required><?= htmlspecialchars($edit_data['content'] ?? '') ?></textarea>
        
        <div style="display: flex; gap: 10px;">
            <select name="news_type" class="modern-input" style="flex: 1; margin:0; cursor:pointer;">
                <option value="standard" <?= ($edit_data && $edit_data['type'] == 'standard') ? 'selected' : '' ?>>Standard (Tirkiz)</option>
                <option value="urgent" <?= ($edit_data && $edit_data['type'] == 'urgent') ? 'selected' : '' ?>>Hitno (Crveno)</option>
            </select>

            <!-- Dinamičko glavno dugme -->
            <button type="submit" name="post_news" class="btn-send" style="flex: 1;">
                <?= ($edit_data) ? 'SAČUVAJ' : 'OBJAVI' ?>
            </button>

            <!-- Dugme za odustajanje (pojavljuje se samo pri editu) -->
            <?php if($edit_data): ?>
                <a href="dashboard.php" class="btn-discard" style="text-decoration: none;">ODUSTANI</a>
            <?php endif; ?>
        </div>
    </form>
</div>

            <?php endif; ?>

            <!-- DINAMIČKI FEED VESTI -->
            <div class="news-feed" id="dynamic-news">
                Učitavanje vesti...
            </div>
        </div>
    </div>

    <script>
let lastTotalUnread = 0;

// 1. Osvežavanje Sidebara
function refreshSidebar() {
    fetch('fetch_sidebar.php').then(r => r.text()).then(data => {
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

// 2. Osvežavanje Vesti (Pametni refresh koji ne prekida kucanje)
function refreshNews() {
    const active = document.activeElement;
    const isTyping = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');

    if (!isTyping) {
        fetch('fetch_news.php')
            .then(r => r.text())
            .then(data => {
                document.getElementById('dynamic-news').innerHTML = data;
            });
    }
}

// 3. Funkcija za Like (AJAX)
function toggleLike(newsId) {
    let fd = new FormData();
    fd.append('toggle_like', '1');
    fd.append('news_id', newsId);

    fetch('dashboard.php', { method: 'POST', body: fd })
        .then(() => refreshNews());
}

// 4. Funkcija za Komentar (AJAX)
function sendComment(newsId) {
    const input = document.getElementById('comm-txt-' + newsId);
    const text = input.value.trim();
    if (!text) return;

    let fd = new FormData();
    fd.append('post_comment', '1');
    fd.append('news_id', newsId);
    fd.append('comment_text', text);

    fetch('dashboard.php', { method: 'POST', body: fd })
        .then(() => {
            input.value = '';
            refreshNews();
        });
}

// 5. NOVO: Funkcija za Brisanje Komentara (AJAX)
function deleteComment(commentId) {
    if (!confirm("Obrisati ovaj komentar?")) return;

    let fd = new FormData();
    fd.append('delete_comment_ajax', '1');
    fd.append('comment_id', commentId);

    fetch('dashboard.php', { method: 'POST', body: fd })
        .then(() => refreshNews());
}

// 6. NOVO: Funkcija za Editovanje Komentara (AJAX + Prompt)
function editComment(commentId) {
    const currentText = document.getElementById('comment-text-' + commentId).innerText;
    const newText = prompt("Izmeni komentar:", currentText);

    if (newText !== null && newText.trim() !== "" && newText !== currentText) {
        let fd = new FormData();
        fd.append('edit_comment_ajax', '1');
        fd.append('comment_id', commentId);
        fd.append('new_text', newText);

        fetch('dashboard.php', { method: 'POST', body: fd })
            .then(() => refreshNews());
    }
}

// Intervali
setInterval(refreshSidebar, 5000);
setInterval(refreshNews, 10000); 

// Inicijalno učitavanje
refreshSidebar();
refreshNews();
</script>

</body>
</html>
