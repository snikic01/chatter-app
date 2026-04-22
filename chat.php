<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    header("Location: index.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$friend_id = (int)$_GET['user_id'];

// Označi kao pročitano
$pdo->prepare("UPDATE private_messages SET seen = 1 WHERE sender_id = ? AND receiver_id = ? AND seen = 0")
    ->execute([$friend_id, $my_id]);

// Podaci o prijatelju
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$friend_id]);
$friend = $stmt->fetch();

// AJAX: Slanje poruke
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['msg'])) {
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
        $tick = ($isMe && $m['seen'] == 1) ? "<div class='seen-tick'>Seen ✓</div>" : "";

        echo "<div class='message-wrapper $class'>
                <div class='message'>" . htmlspecialchars($m['message']) . "$tick</div>
              </div>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Chat sa <?php echo $friend['username']; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="chat-area">
        <div class="chat-header">
            <div>
                <span class="status-dot">●</span>
                <strong><?php echo htmlspecialchars($friend['username']); ?></strong>
            </div>
            <a href="dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 20px;">&times;</a>
        </div>

        <div id="chat-box">Učitavanje...</div>

        <div class="input-container">
            <form id="chat-form">
                <input type="text" id="msg-input" placeholder="Napiši poruku..." autocomplete="off">
                <button type="submit" class="btn-send">Pošalji</button>
            </form>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const friendId = <?php echo $friend_id; ?>;

        function fetchMessages() {
            fetch(`chat.php?user_id=${friendId}&fetch=1&t=${Date.now()}`)
                .then(r => r.text())
                .then(data => {
                    const shouldScroll = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;
                    chatBox.innerHTML = data;
                    if (shouldScroll) chatBox.scrollTop = chatBox.scrollHeight;
                });
        }

        document.getElementById('chat-form').onsubmit = (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            if (!input.value.trim()) return;

            let fd = new FormData();
            fd.append('msg', input.value);
            fetch(`chat.php?user_id=${friendId}`, { method: 'POST', body: fd })
                .then(() => {
                    input.value = '';
                    fetchMessages();
                });
        };

        setInterval(fetchMessages, 2000);
        fetchMessages();
    </script>
</body>
</html>
