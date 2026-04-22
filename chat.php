<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$friend_id = (int)$_GET['user_id'];

// Uzmi ime prijatelja za naslov
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$friend_id]);
$friend = $stmt->fetch();

// LOGIKA ZA SLANJE PORUKE (AJAX poziv)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['msg'])) {
    $msg = trim($_POST['msg']);
    if (!empty($msg)) {
        $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$my_id, $friend_id, $msg]);
    }
    exit();
}

// LOGIKA ZA FETCH PORUKA (AJAX poziv)
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("
        SELECT * FROM private_messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?) 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$my_id, $friend_id, $friend_id, $my_id]);
    $messages = $stmt->fetchAll();

    foreach ($messages as $m) {
        $class = ($m['sender_id'] == $my_id) ? 'my-msg' : 'friend-msg';
        echo "<div class='message-wrapper $class'><div class='message'>" . htmlspecialchars($m['message']) . "</div></div>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat sa <?php echo $friend['username']; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: #1a1a1a; color: white; font-family: sans-serif; margin: 0; display: flex; flex-direction: column; height: 100vh; }
        .header { background: #00adb5; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; }
        #chat-box { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .message-wrapper { display: flex; width: 100%; }
        .my-msg { justify-content: flex-end; }
        .friend-msg { justify-content: flex-start; }
        .message { padding: 10px 15px; border-radius: 15px; max-width: 70%; }
        .my-msg .message { background: #00adb5; border-bottom-right-radius: 2px; }
        .friend-msg .message { background: #393e46; border-bottom-left-radius: 2px; }
        .input-area { padding: 20px; background: #222; display: flex; gap: 10px; }
        input { flex: 1; padding: 10px; background: #333; border: 1px solid #444; color: white; border-radius: 5px; }
        button { background: #00adb5; border: none; padding: 10px 20px; color: white; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="header">
        <span>Čet sa: <?php echo htmlspecialchars($friend['username']); ?></span>
        <a href="dashboard.php" style="color: white; text-decoration: none;">X</a>
    </div>

    <div id="chat-box">Učitavanje poruka...</div>

    <form class="input-area" id="chat-form">
        <input type="text" id="msg-input" placeholder="Napiši poruku..." autocomplete="off">
        <button type="submit">Pošalji</button>
    </form>

    <script>
        const chatBox = document.getElementById('chat-box');
        const friendId = <?php echo $friend_id; ?>;

        function fetchMessages() {
            fetch(`chat.php?user_id=${friendId}&fetch=1`)
                .then(r => r.text())
                .then(data => {
                    chatBox.innerHTML = data;
                });
        }

        document.getElementById('chat-form').onsubmit = (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            const msg = input.value.trim();
            if (!msg) return;

            let fd = new FormData();
            fd.append('msg', msg);

            fetch(`chat.php?user_id=${friendId}`, { method: 'POST', body: fd })
                .then(() => {
                    input.value = '';
                    fetchMessages();
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        };

        setInterval(fetchMessages, 2000);
        fetchMessages();
    </script>
</body>
</html>
