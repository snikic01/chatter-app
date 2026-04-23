<?php
session_start();
require_once 'db_chatter.php';

// --- GHOST MODE PROVERA ---
$is_ghost = isset($_GET['ghost']) && $_GET['ghost'] === 'true' && $_SESSION['username'] === 'snikic01';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$group_id = (int)$_GET['id'];

// --- PROVERA ČLANSTVA ---
if (!$is_ghost) {
    $check = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $check->execute([$group_id, $my_id]);
    if (!$check->fetch()) {
        die("Nisi član ove grupe.");
    }

    $pdo->prepare("
        INSERT IGNORE INTO group_message_seen (message_id, user_id)
        SELECT id, ? FROM private_messages WHERE group_id = ? AND sender_id != ?
    ")->execute([$my_id, $group_id, $my_id]);
}

// Podaci o grupi
$stmt = $pdo->prepare("SELECT * FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    $back = $is_ghost ? "admin_logs.php" : "dashboard.php";
    die("<body style='background:#121212;color:white;text-align:center;padding-top:50px;'>Grupa ne postoji. <a href='$back' style='color:var(--accent)'>Nazad</a></body>");
}

// LOGIKA: Dodavanje člana
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_ghost ? "[GHOST] " : ""; ?>Grupa | <?php echo $group['name']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .group-container { display: flex; flex: 1; height: 100vh; overflow: hidden; }
        
        /* Članovi sidebar - DODATA TRANZICIJA I KOLAPS */
        .members-sidebar { 
            width: 240px; 
            background: var(--sidebar-bg); 
            border-left: 1px solid var(--border); 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            transition: 0.3s ease; /* Glatko otvaranje */
        }
        
        .members-sidebar.collapsed { 
            width: 0; 
            padding: 0; 
            border-left: none;
            opacity: 0;
            pointer-events: none;
        }

        .toggle-members-btn {
            background: var(--card-bg);
            color: var(--accent);
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 12px;
            transition: 0.2s;
        }
        
        .toggle-members-btn:hover { background: var(--accent); color: white; }

        .member-item { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .member-status-row { display: flex; align-items: center; font-size: 14px; }
        .status-dot { margin-right: 8px; font-size: 10px; }
        .last-seen-text { font-size: 9px; color: var(--text-muted); padding-left: 18px; margin-top: 2px; }
        select { width: 100%; padding: 8px; background: #111; color: white; border: 1px solid var(--border); border-radius: 5px; margin-top: 10px; cursor: pointer; }
        .ghost-badge { background: rgba(108, 92, 231, 0.2); color: #6c5ce7; padding: 2px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; margin-left: 10px; }

        /* Mobilna optimizacija: sakrij glavni sidebar */
        @media (max-width: 768px) {
            .sidebar { display: none !important; }
            .members-sidebar { position: absolute; right: 0; height: 100%; z-index: 10; box-shadow: -5px 0 15px rgba(0,0,0,0.5); }
        }
    </style>
</head>
<body>
<div class="group-container">
    <div class="chat-area">
        <div class="chat-header">
            <div style="display: flex; align-items: center;">
                <!-- DUGME ZA PROŠIRIVANJE -->
                <button class="toggle-members-btn" onclick="toggleMembers()" title="Članovi">👥</button>
                
                <span style="color: var(--group-gold);">#</span> 
                <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                <?php if ($is_ghost): ?>
                    <span class="ghost-badge">GHOST MODE</span>
                <?php endif; ?>
            </div>
            
            <?php $exit_to = $is_ghost ? "admin_logs.php" : "dashboard.php"; ?>
            <a href="<?php echo $exit_to; ?>" style="color: var(--text-muted); text-decoration: none; font-size: 20px;">&times;</a>
        </div>

        <div id="chat-box">Učitavanje...</div>

        <div class="input-container">
            <?php if (!$is_ghost): ?>
                <form id="chat-form">
                    <input type="text" id="msg-input" placeholder="Napiši nešto grupi..." autocomplete="off">
                    <button type="submit" class="btn-send">Pošalji</button>
                </form>
            <?php else: ?>
                <div style="text-align:center; color:var(--text-muted); font-size:12px; font-style:italic;">Ghost mode: Pisanje onemogućeno.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Članovi Sidebar -->
    <div class="members-sidebar" id="members-sidebar">
        <div class="section-title">Članovi grupe</div>
        <div style="flex: 1; overflow-y: auto;">
            <?php
            $stmt_m = $pdo->prepare("SELECT u.username, u.last_seen FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
            $stmt_m->execute([$group_id]);
            while($m = $stmt_m->fetch()) {
                $m_online = (strtotime($m['last_seen']) > (time() - 300));
                $m_color = $m_online ? 'var(--success)' : 'var(--text-muted)';
                echo "<div class='member-item'>";
                echo "<div class='member-status-row'><span class='status-dot' style='color: $m_color;'>●</span> " . htmlspecialchars($m['username']) . "</div>";
                if (!$m_online && function_exists('time_ago')) {
                    echo "<div class='last-seen-text'>" . time_ago($m['last_seen']) . "</div>";
                }
                echo "</div>";
            }
            ?>
        </div>

        <?php if (!$is_ghost): ?>
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
        <?php endif; ?>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const isGhost = <?php echo $is_ghost ? 'true' : 'false'; ?>;

    // FUNKCIJA ZA SKLAPANJE SIDEBAR-A
    function toggleMembers() {
        const sidebar = document.getElementById('members-sidebar');
        sidebar.classList.toggle('collapsed');
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
