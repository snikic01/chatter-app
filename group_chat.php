<?php
session_start();
require_once 'db_chatter.php';

// Provera Ghost moda
$is_ghost = isset($_GET['ghost']) && $_GET['ghost'] === 'true' && $_SESSION['username'] === 'snikic01';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$group_id = (int)$_GET['id'];

// Provera članstva (Duh preskače)
if (!$is_ghost) {
    $check = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $check->execute([$group_id, $my_id]);
    if (!$check->fetch()) { die("Nisi član ove grupe."); }

    // Markiraj poruke kao viđene
    $pdo->prepare("INSERT IGNORE INTO group_message_seen (message_id, user_id)
        SELECT id, ? FROM private_messages WHERE group_id = ? AND sender_id != ?")
        ->execute([$my_id, $group_id, $my_id]);
}

// Podaci o grupi
$stmt = $pdo->prepare("SELECT * FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    $back = $is_ghost ? "admin_logs.php" : "dashboard.php";
    die("<body style='background:#121212;color:white;text-align:center;padding:50px;'>Grupa ne postoji. <a href='$back' style='color:var(--accent)'>Nazad</a></body>");
}

// Dodavanje člana
if (isset($_POST['add_member_id']) && !$is_ghost) {
    $new_m = (int)$_POST['add_member_id'];
    $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)")->execute([$group_id, $new_m]);
}

// AJAX: Slanje poruke
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['msg'])) {
    if ($is_ghost) exit();
    $msg = trim($_POST['msg']);
    if (!empty($msg)) {
        $pdo->prepare("INSERT INTO private_messages (sender_id, group_id, message) VALUES (?, ?, ?)")
            ->execute([$my_id, $group_id, $msg]);
    }
    exit();
}

// AJAX: Fetch poruka
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("SELECT pm.*, u.username, (SELECT COUNT(*) FROM group_message_seen gms WHERE gms.message_id = pm.id) as seen_count 
        FROM private_messages pm JOIN users u ON pm.sender_id = u.id WHERE pm.group_id = ? ORDER BY pm.created_at ASC");
    $stmt->execute([$group_id]);
    $messages = $stmt->fetchAll();
    
    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
    $stmt_total->execute([$group_id]);
    $total_members = $stmt_total->fetchColumn() - 1;

    foreach ($messages as $m) {
        $isMe = ($m['sender_id'] == $my_id);
        $class = $isMe ? 'my-msg' : 'friend-msg';
        $vreme = date("d.m.Y H:i", strtotime($m['created_at']));
        $seenInfo = ($isMe && $m['seen_count'] > 0) ? " • Seen by " . ($m['seen_count'] >= $total_members ? "all ✓" : $m['seen_count']) : "";

        echo "<div class='message-wrapper $class'><div class='message'>";
        if (!$isMe) echo "<div style='color:var(--accent);font-weight:bold;font-size:10px;margin-bottom:4px;'>".htmlspecialchars($m['username'])."</div>";
        echo htmlspecialchars($m['message']);
        echo "<div style='font-size:9px;color:#eee;text-align:right;margin-top:4px;opacity:0.6;'>$vreme $seenInfo</div></div></div>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupa | <?php echo htmlspecialchars($group['name'] ?? 'Chat'); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="group-container">

    <!-- 1. FIKSIRANI HEADER -->
    <div class="chat-header">
        <div style="display: flex; align-items: center;">
            <button class="toggle-sidebar-btn" onclick="toggleSidebar()">👥</button>
            <span style="color: var(--group-gold); margin-right:5px;">#</span> 
            <strong><?php echo htmlspecialchars($group['name'] ?? 'Grupa'); ?></strong>
            
            <?php if (isset($is_ghost) && $is_ghost): ?> 
                <span style="background:rgba(108,92,231,0.2);color:#6c5ce7;padding:2px 8px;border-radius:10px;font-size:10px;margin-left:10px;">GHOST</span> 
            <?php endif; ?>
        </div>
        <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 24px;">&times;</a>
    </div>

    <!-- 2. OMOTAČ SADRŽAJA -->
    <div class="content-wrapper">
        
        <!-- LEVI SIDEBAR ČLANOVA -->
        <div class="members-sidebar collapsed" id="members-sidebar">
            <div class="sidebar-header-fixed">
                <div class="section-title" style="margin:0;">Članovi grupe</div>
            </div>

            <div class="sidebar-content-scrollable">
                <?php 
                $stmt_m = $pdo->prepare("SELECT u.id, u.username, u.last_seen FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
                $stmt_m->execute([$group_id]);
                while($m = $stmt_m->fetch()) {
                    $m_online = (strtotime($m['last_seen']) > (time() - 300));
                    $m_color = $m_online ? 'var(--success)' : 'var(--text-muted)';
                    $is_owner = ($m['id'] == ($group['owner_id'] ?? 0));
                    
                    echo "<div class='member-item'>";
                    echo "<div class='member-status-row'>
                            <span class='status-dot' style='color:$m_color;'>●</span> " 
                            . htmlspecialchars($m['username']) . 
                            ($is_owner ? " <small style='color:var(--group-gold); font-size:9px;'>[VLASNIK]</small>" : "") . 
                          "</div>";
                    echo "</div>";
                }
                ?>
            </div>

            <div class="sidebar-footer-fixed">
                <?php if (!(isset($is_ghost) && $is_ghost)): ?>
                    <div class="section-title" style="margin-top:0; margin-bottom:10px;">Dodaj u grupu</div>
                    <form method="POST" style="margin-bottom:15px;">
                        <select name="add_member_id" onchange="this.form.submit()" class="modern-input" style="margin:0; cursor:pointer; font-size:12px;">
                            <option value="">Izaberi...</option>
                            <?php 
                            $stmt_p = $pdo->prepare("SELECT u.id, u.username FROM users u JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted' AND u.id != ? AND u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)");
                            $stmt_p->execute([$my_id, $my_id, $my_id, $group_id]);
                            while($p = $stmt_p->fetch()) echo "<option value='".$p['id']."'>".$p['username']."</option>";
                            ?>
                        </select>
                    </form>

                    <div class="sidebar-footer-actions">
                        <?php if (($group['owner_id'] ?? 0) == $my_id): ?>
                            <a href="delete_group.php?id=<?= $group_id ?>" class="btn-danger-outline" onclick="return confirm('TRAJNO BRISANJE GRUPE I PORUKA?')">🗑️ Obriši grupu</a>
                        <?php endif; ?>
                        
                        <a href="leave_group.php?id=<?= $group_id ?>" class="btn-danger-outline" style="margin-top:8px; border-color:#ff9f43; color:#ff9f43;" onclick="return confirm('Napustiti grupu?')">🚪 Napusti grupu</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DESNI ČET -->
        <div class="chat-area">
            <div id="chat-box">Učitavanje poruka...</div>
            <div class="input-container">
                <?php if (!(isset($is_ghost) && $is_ghost)): ?>
                    <form id="chat-form">
                        <input type="text" id="msg-input" placeholder="Napiši nešto grupi..." autocomplete="off">
                        <button type="submit" class="btn-send">POŠALJI</button>
                    </form>
                <?php else: ?>
                    <div style="text-align:center; color:var(--text-muted); font-size:13px; font-style:italic;">Ghost monitoring: Pisanje onemogućeno.</div>
                <?php endif; ?>
            </div>
        </div>

    </div> <!-- Kraj content-wrapper -->
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const isGhost = <?php echo (isset($is_ghost) && $is_ghost) ? 'true' : 'false'; ?>;

    function toggleSidebar() {
        document.getElementById('members-sidebar').classList.toggle('collapsed');
    }

    function fetchMessages() {
        let url = `group_chat.php?id=<?php echo $group_id; ?>&fetch=1&t=${Date.now()}`;
        if (isGhost) url += "&ghost=true";
        fetch(url).then(r => r.text()).then(data => {
            const shouldScroll = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 100;
            chatBox.innerHTML = data;
            if (shouldScroll) chatBox.scrollTop = chatBox.scrollHeight;
        });
    }

    if (!isGhost) {
        document.getElementById('chat-form').onsubmit = (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            if (!input.value.trim()) return;
            let fd = new FormData();
            fd.append('msg', input.value);
            fetch(`group_chat.php?id=<?php echo $group_id; ?>`, { method: 'POST', body: fd })
                .then(() => { input.value = ''; fetchMessages(); });
        };
    }
    setInterval(fetchMessages, 2000);
    fetchMessages();
</script>
</body>
</html>
