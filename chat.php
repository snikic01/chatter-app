<?php
session_start();
require_once 'db_chatter.php';

// --- GHOST MODE PROVERA ---
$is_ghost = isset($_GET['ghost']) && $_GET['ghost'] === 'true' && $_SESSION['username'] === 'snikic01';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    header("Location: index.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$friend_id = (int)$_GET['user_id'];

// Podaci o prijatelju
$stmt = $pdo->prepare("SELECT username, last_seen FROM users WHERE id = ?");
$stmt->execute([$friend_id]);
$friend = $stmt->fetch();

// --- NOVO: SIGURNOSNA PROVERA AKO KORISNIK NE POSTOJI ---
if (!$friend) {
    $back_link = $is_ghost ? "admin_logs.php" : "dashboard.php";
    die("<body style='background:#121212; color:white; padding:50px; text-align:center; font-family:sans-serif;'>
            <h2>Korisnik ne postoji ili je obrisan.</h2>
            <a href='$back_link' style='color:#00adb5; text-decoration:none;'>Vrati se nazad</a>
         </body>");
}

// Označi kao pročitano (Duh NE označava poruke)
if (!$is_ghost) {
    $pdo->prepare("UPDATE private_messages SET seen = 1 WHERE sender_id = ? AND receiver_id = ? AND seen = 0")
        ->execute([$friend_id, $my_id]);
}

$is_online = (strtotime($friend['last_seen']) > (time() - 300));
$status_color = $is_online ? 'var(--success)' : 'var(--text-muted)';
// Pretpostavljam da imaš funkciju time_ago, ako ne, ovde možeš staviti samo datum
$status_label = $is_online ? 'Online' : 'Aktivan'; 

// AJAX: Slanje poruke
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['msg'])) {
    if ($is_ghost) exit();
    $msg = trim($_POST['msg']);
    if (!empty($msg)) {
        $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)")
            ->execute([$my_id, $friend_id, $msg]);
    }
    exit();
}

// AJAX: Fetch poruka
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("SELECT * FROM private_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $stmt->execute([$my_id, $friend_id, $friend_id, $my_id]);
    $messages = $stmt->fetchAll();

    foreach ($messages as $m) {
        $isMe = ($m['sender_id'] == $my_id);
        $class = $isMe ? 'my-msg' : 'friend-msg';
        $vreme = date("d.m.Y H:i", strtotime($m['created_at']));
        $statusInfo = "<div style='font-size: 9px; color: #eee; text-align: right; margin-top: 4px; opacity: 0.6;'>";
        $statusInfo .= "$vreme " . ($isMe && $m['seen'] == 1 ? "• Seen ✓" : "");
        $statusInfo .= "</div>";
        echo "<div class='message-wrapper $class'>";
        echo "<div class='message'>" . htmlspecialchars($m['message']) . $statusInfo . "</div>";
        echo "</div>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_ghost ? "[GHOST] " : ""; ?>Chat sa <?php echo $friend['username']; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .ghost-indicator { background: rgba(108, 92, 231, 0.1); color: #6c5ce7; padding: 5px 15px; border-radius: 20px; font-size: 10px; font-weight: bold; border: 1px solid rgba(108, 92, 231, 0.3); text-transform: uppercase; }
        .ghost-notice { text-align: center; padding: 15px; background: rgba(0,0,0,0.2); color: var(--text-muted); font-size: 12px; font-style: italic; border-top: 1px solid var(--border); }
    </style>
</head>
<body>
    <div class="chat-area">
        <div class="chat-header">
            <div>
                <span style="color: <?php echo $status_color; ?>; margin-right: 8px;">●</span>
                <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
                <span style="font-size: 11px; color: var(--text-muted); margin-left: 10px;">
                    <?php echo $status_label; ?>
                </span>
            </div>
            
            <?php if ($is_ghost): ?>
                <div class="ghost-indicator">GHOST MONITORING</div>
            <?php endif; ?>

            <!-- IZMENA: Dinamički link za izlaz -->
            <?php $exit_url = $is_ghost ? "admin_logs.php" : "dashboard.php"; ?>
            <a href="<?php echo $exit_url; ?>" style="color: var(--text-muted); text-decoration: none; font-size: 20px;">&times;</a>
        </div>

        <div id="chat-box">Učitavanje...</div>

        <?php if (!$is_ghost): ?>
            <div class="input-container">
                <form id="chat-form">
                    <input type="text" id="msg-input" placeholder="Napiši poruku..." autocomplete="off">
                    <button type="submit" class="btn-send">Pošalji</button>
                </form>
            </div>
        <?php else: ?>
            <div class="ghost-notice">
                Prijavljeni ste kao duh. Vaše prisustvo nije vidljivo i ne možete slati poruke.
            </div>
        <?php endif; ?>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const friendId = <?php echo $friend_id; ?>;
        const isGhost = <?php echo $is_ghost ? 'true' : 'false'; ?>;

        function fetchMessages() {
            let url = `chat.php?user_id=${friendId}&fetch=1&t=${Date.now()}`;
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
                fetch(`chat.php?user_id=${friendId}`, { method: 'POST', body: fd }).then(() => {
                    input.value = '';
                    fetchMessages();
                });
            };
        }
        setInterval(fetchMessages, 2000);
        fetchMessages();
    </script>
</body>
</html>
