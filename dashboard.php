<?php
session_start();
require_once 'db_chatter.php';

// Provera da li je ulogovan admin (snikic01)
$is_admin = ($my_user === 'snikic01');

// Logika za objavljivanje vesti (samo ako si admin)
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


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$my_user = $_SESSION['username'];
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
    <div class="empty-state">
        <h1 style="color: var(--accent);">Zdravo, <?php echo htmlspecialchars($my_user); ?>! 👋</h1>
        <p>Izaberi konverzaciju sa leve strane da počneš.</p>
    </div>
</div>

</body>
</html>
