<?php
session_start();
require_once 'db_chatter.php';

// 1. Prvo provera sesije
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. TEK SADA definiši varijable iz sesije
$my_id = $_SESSION['user_id'];
$my_user = $_SESSION['username'];

// 3. SADA proveri da li je ulogovani korisnik admin
$is_admin = ($my_user === 'snikic01');

// 4. Logika za objavljivanje vesti
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

    <div class="search-box">
        <form action="search.php" method="GET">
            <input type="text" name="q" placeholder="Pretraži korisnike...">
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

        <!-- GRUPE -->
        <div class="section-title">Grupe</div>
        <form action="create_group.php" method="POST" class="group-form" style="display: flex; gap: 5px; margin-bottom: 10px;">
            <input type="text" name="group_name" placeholder="Nova grupa..." required>
            <button type="submit" class="btn-icon">+</button>
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
    <div class="main-chat" style="justify-content: flex-start; overflow-y: auto; padding: 40px;">
    <div style="max-width: 700px; width: 100%;">
        <h1 style="color: var(--accent); margin-bottom: 5px;">Zdravo, <?php echo htmlspecialchars($my_user); ?>! 👋</h1>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Dobrodošli na Chatter Global Board.</p>

        <!-- FORMA ZA ADMINA -->
        <?php if ($is_admin): ?>
            <div style="background: var(--sidebar-bg); padding: 20px; border-radius: 10px; border: 1px solid var(--accent); margin-bottom: 40px;">
                <h3 style="margin-top: 0; color: var(--accent);">Nova objava</h3>
                <form method="POST">
                    <input type="text" name="news_title" placeholder="Naslov vesti..." required 
                           style="width: 100%; padding: 10px; background: #111; border: 1px solid var(--border); color: white; border-radius: 5px; margin-bottom: 10px;">
                    <textarea name="news_content" placeholder="Šta ti je na umu?" required 
                              style="width: 100%; padding: 10px; background: #111; border: 1px solid var(--border); color: white; border-radius: 5px; height: 100px; font-family: inherit;"></textarea>
                    <button type="submit" name="post_news" class="btn-send" style="margin-top: 10px; width: 100%; height: 40px;">Objavi na zid</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- PRIKAZ VESTI (VIDE SVI) -->
        <div class="news-feed">
            <?php
            $news = $pdo->query("SELECT * FROM admin_news ORDER BY created_at DESC")->fetchAll();
            foreach ($news as $n): ?>
                <div style="background: var(--sidebar-bg); padding: 20px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 20px;">
                    <small style="color: var(--accent); font-weight: bold;">ADMIN POST • <?php echo date("d.m.Y H:i", strtotime($n['created_at'])); ?></small>
                    <h2 style="margin: 10px 0; color: var(--text-main);"><?php echo htmlspecialchars($n['title']); ?></h2>
                    <p style="color: #ccc; line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($n['content']); ?></p>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($news)): ?>
                <p style="text-align: center; color: var(--text-muted);">Trenutno nema novih vesti na tabli.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>

</body>
</html>
