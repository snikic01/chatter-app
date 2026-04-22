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

<div class="sidebar">
    <div class="sidebar-header">Chatter Dashboard</div>
    
    <div class="user-section">
        <small>Prijavljen kao:</small><br>
        <strong><?php echo htmlspecialchars($my_user); ?></strong>
    </div>

    <!-- PRETRAGA (Sada identična formi za grupe) -->
    <div class="search-box" style="padding: 15px 15px 0 15px;">
        <form action="search.php" method="GET" style="display: flex; gap: 5px; align-items: center;">
            <input type="text" name="q" placeholder="Pronađi prijatelje..." class="modern-input" 
                   style="margin:0; padding:8px; flex:1; height:35px; font-size:12px;">
            <button type="submit" class="btn-send" style="width:40px; height:35px; padding:0; display:flex; justify-content:center; align-items:center;">🔍</button>
        </form>
    </div>

    <div class="scroll-area">
        <!-- ZAHTEVI -->
        <?php
        $stmt_req = $pdo->prepare("SELECT u.username, u.id FROM users u JOIN friends f ON u.id = f.user_id WHERE f.friend_id = ? AND f.status = 'pending'");
        $stmt_req->execute([$my_id]);
        $requests = $stmt_req->fetchAll();
        if (count($requests) > 0): ?>
            <div class="section-title">Zahtevi</div>
            <?php foreach ($requests as $r): ?>
                <div class="item-row" style="background: rgba(70, 209, 96, 0.1);">
                    <span><?php echo htmlspecialchars($r['username']); ?></span>
                    <a href="accept_friend.php?id=<?php echo $r['id']; ?>" style="color: var(--success); font-weight: bold; text-decoration: none;">[✓]</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- PRIJATELJI -->
        <div class="section-title">Prijatelji</div>
        <?php
        $stmt = $pdo->prepare("SELECT u.username, u.id FROM users u JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) WHERE (f.user_id = ? OR f.friend_id = ?) AND u.id != ? AND f.status = 'accepted'");
        $stmt->execute([$my_id, $my_id, $my_id]);
        while($f = $stmt->fetch()):
            $st_u = $pdo->prepare("SELECT COUNT(*) FROM private_messages WHERE sender_id = ? AND receiver_id = ? AND seen = 0");
            $st_u->execute([$f['id'], $my_id]);
            $count = $st_u->fetchColumn();
        ?>
            <a href="chat.php?user_id=<?php echo $f['id']; ?>" class="item-row">
                <span>● <?php echo htmlspecialchars($f['username']); ?></span>
                <?php if($count > 0) echo "<span class='badge'>$count</span>"; ?>
            </a>
        <?php endwhile; ?>

        <!-- GRUPE (Identična struktura kao pretraga) -->
        <div class="section-title">Grupe</div>
        <form action="create_group.php" method="POST" style="display: flex; gap: 5px; margin-bottom: 10px; align-items: center;">
            <input type="text" name="group_name" class="modern-input" 
                   style="margin:0; padding:8px; flex:1; height:35px; font-size:12px;" placeholder="Nova grupa..." required>
            <button type="submit" class="btn-send" style="width:40px; height:35px;">+</button>
        </form>

        <?php
        $stmt_g = $pdo->prepare("SELECT g.* FROM chat_groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
        $stmt_g->execute([$my_id]);
        while($g = $stmt_g->fetch()): ?>
            <a href="group_chat.php?id=<?php echo $g['id']; ?>" class="item-row" style="color: var(--group-gold);">
                # <?php echo htmlspecialchars($g['name']); ?>
            </a>
        <?php endwhile; ?>
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
            <?php
            $news = $pdo->query("SELECT * FROM admin_news ORDER BY created_at DESC")->fetchAll();
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

</body>
</html>
