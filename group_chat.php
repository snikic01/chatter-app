<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$group_id = (int)$_GET['id'];

// Provera članstva
$check = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$check->execute([$group_id, $my_id]);
if (!$check->fetch()) {
    die("Nisi član ove grupe.");
}

// Markiraj poruke kao viđene
$pdo->prepare("
    INSERT IGNORE INTO group_message_seen (message_id, user_id)
    SELECT id, ? FROM private_messages 
    WHERE group_id = ? AND sender_id != ?
")->execute([$my_id, $group_id, $my_id]);

// Podaci o grupi
$stmt = $pdo->prepare("SELECT * FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

// LOGIKA: Dodavanje člana
if (isset($_POST['add_member_id'])) {
    $new_m = (int)$_POST['add_member_id'];
    $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)")->execute([$group_id, $new_m]);
}

// AJAX: Slanje poruke
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['msg'])) {
    $msg = trim($_POST['msg']);
    if (!empty($msg)) {
        $pdo->prepare("INSERT INTO private_messages (sender_id, group_id, message) VALUES (?, ?, ?)")
            ->execute([$my_id, $group_id, $msg]);
    }
    exit();
}

// AJAX: Fetch poruka
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("
        SELECT pm.*, u.username,
        (SELECT COUNT(*) FROM group_message_seen gms WHERE gms.message_id = pm.id) as seen_count
        FROM private_messages pm 
        JOIN users u ON pm.sender_id = u.id 
        WHERE pm.group_id = ? 
        ORDER BY pm.created_at ASC
    ");
    $stmt->execute([$group_id]);
    $messages = $stmt->fetchAll();

    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
    $stmt_total->execute([$group_id]);
    $total_members = $stmt_total->fetchColumn() - 1;

    foreach ($messages as $m) {
        $isMe = ($m['sender_id'] == $my_id);
        $class = $isMe ? 'my-msg' : 'friend-msg';
        $vreme = date("d.m.Y H:i", strtotime($m['created_at']));

        $seenInfo = "";
        if ($isMe && $m['seen_count'] > 0) {
            $statusText = ($m['seen_count'] >= $total_members) ? "all ✓" : $m['seen_count'];
            $seenInfo = " • Seen by $statusText";
        }

        echo "<div class='message-wrapper $class'>";
        echo "<div class='message'>";
        
        if (!$isMe) {
            echo "<div style='display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;'>";
            echo "<small style='color: var(--accent); font-weight: bold;'>" . htmlspecialchars($m['username']) . "</small>";
            echo "<small style='color: #777; font-size: 8px; margin-left: 10px;'>$vreme</small>";
            echo "</div>";
        }
        
        echo htmlspecialchars($m['message']);
        
        if ($isMe) {
            echo "<div style='font-size: 9px; color: #eee; text-align: right; margin-top: 4px; opacity: 0.6;'>$vreme $seenInfo</div>";
        }

        echo "</div></div>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Grupa | <?php echo $group['name']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .group-container { display: flex; flex: 1; height: 100vh; }
        .members-sidebar { width: 240px; background: var(--sidebar-bg); border-left: 1px solid var(--border); padding: 20px; display: flex; flex-direction: column; }
        .member-item { padding: 8px 0; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .member-item span { color: var(--success); margin-right: 8px; }
        select { width: 100%; padding: 8px; background: #111; color: white; border: 1px solid var(--border); border-radius: 5px; margin-top: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="group-container">
        <div class="chat-area">
            <div class="chat-header">
                <div>
                    <span style="color: var(--group-gold);">#</span>
                    <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                    <?php if ($group['owner_id'] == $my_id): ?>
                        <a href="delete_group.php?id=<?php echo $group_id; ?>" onclick="return confirm('Obriši grupu?')" style="margin-left: 15px; color: var(--danger); font-size: 11px; text-decoration: none;">[Obriši grupu]</a>
                    <?php endif; ?>
                </div>
                <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 20px;">&times;</a>
            </div>
            <div id="chat-box">Učitavanje...</div>
            <div class="input-container">
                <form id="chat-form">
                    <input type="text" id="msg-input" placeholder="Napiši nešto grupi..." autocomplete="off">
                    <button type="submit" class="btn-send">Pošalji</button>
                </form>
            </div>
        </div>
        <div class="members-sidebar">
            <div class="section-title">Članovi grupe</div>
            <div style="flex: 1; overflow-y: auto;">
                <?php
                $stmt_m = $pdo->prepare("SELECT u.username FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
                $stmt_m->execute([$group_id]);
                while($m = $stmt_m->fetch()) echo "<div class='member-item'><span>●</span>" . htmlspecialchars($m['username']) . "</div>";
                ?>
            </div>
            <div class="section-title" style="margin-top: 20px;">Dodaj u grupu</div>
            <form method="POST">
                <select name="add_member_id" onchange="this.form.submit()">
                    <option value="">Izaberi...</option>
                    <?php
                    $stmt_p = $pdo->prepare("SELECT u.id, u.username FROM users u JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted' AND u.id != ? AND u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)");
                    $stmt_p->execute([$my_id, $my_id, $my_id, $group_id]);
                    while($p = $stmt_p->fetch()) echo "<option value='".$p['id']."'>".$p['username']."</option>";
                    ?>
                </select>
            </form>
        </div>
    </div>
    <script>
        const chatBox = document.getElementById('chat-box');
        function fetchMessages() {
            fetch(`group_chat.php?id=<?php echo $group_id; ?>&fetch=1&t=${Date.now()}`)
                .then(r => r.text()).then(data => {
                    const shouldScroll = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;
                    chatBox.innerHTML = data;
                    if (shouldScroll) chatBox.scrollTop = chatBox.scrollHeight;
                });
        }
        document.getElementById('chat-form').onsubmit = (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            if (!input.value.trim()) return;
            let fd = new FormData(); fd.append('msg', input.value);
            fetch(`group_chat.php?id=<?php echo $group_id; ?>`, { method: 'POST', body: fd })
                .then(() => { input.value = ''; fetchMessages(); });
        };
        setInterval(fetchMessages, 2000); fetchMessages();
    </script>
</body>
</html>
