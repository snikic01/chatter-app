<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$my_id = $_SESSION['user_id'];
$group_id = (int)$_GET['id'];

// Proveri da li je korisnik zapravo član ove grupe
$check_member = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$check_member->execute([$group_id, $my_id]);
if (!$check_member->fetch()) {
    die("Nisi član ove grupe.");
}

// Uzmi informacije o grupi
$stmt = $pdo->prepare("SELECT * FROM chat_groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

// LOGIKA ZA DODAVANJE ČLANA
if (isset($_POST['add_member_id'])) {
    $new_member = (int)$_POST['add_member_id'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)");
    $stmt->execute([$group_id, $new_member]);
}

// LOGIKA ZA SLANJE PORUKE (AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['msg'])) {
    $msg = trim($_POST['msg']);
    if (!empty($msg)) {
        $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, group_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$my_id, $group_id, $msg]);
    }
    exit();
}

// LOGIKA ZA FETCH PORUKA (AJAX)
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("
        SELECT pm.*, u.username 
        FROM private_messages pm 
        JOIN users u ON pm.sender_id = u.id 
        WHERE pm.group_id = ? 
        ORDER BY pm.created_at ASC
    ");
    $stmt->execute([$group_id]);
    $messages = $stmt->fetchAll();

    foreach ($messages as $m) {
        $class = ($m['sender_id'] == $my_id) ? 'my-msg' : 'friend-msg';
        echo "<div class='message-wrapper $class'>";
        echo "<div class='message'>";
        if ($m['sender_id'] != $my_id) echo "<small style='color: #00adb5; display:block; font-weight:bold;'>" . htmlspecialchars($m['username']) . "</small>";
        echo htmlspecialchars($m['message']);
        echo "</div></div>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Grupa: <?php echo $group['name']; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: #1a1a1a; color: white; font-family: sans-serif; margin: 0; display: flex; height: 100vh; }
        .chat-area { flex: 1; display: flex; flex-direction: column; border-right: 1px solid #333; }
        .header { background: #00adb5; padding: 15px; font-weight: bold; }
        #chat-box { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .message-wrapper { display: flex; width: 100%; }
        .my-msg { justify-content: flex-end; }
        .friend-msg { justify-content: flex-start; }
        .message { padding: 10px 15px; border-radius: 15px; max-width: 70%; background: #393e46; }
        .my-msg .message { background: #00adb5; border-bottom-right-radius: 2px; }
        .input-area { padding: 20px; background: #222; display: flex; gap: 10px; }
        input, select { flex: 1; padding: 10px; background: #333; border: 1px solid #444; color: white; border-radius: 5px; }
        
        .members-sidebar { width: 200px; background: #252525; padding: 20px; font-size: 14px; }
        .member-name { padding: 5px 0; color: #aaa; border-bottom: 1px solid #333; }
    </style>
</head>
<body>
    <div class="chat-area">
        <div class="header">
            # <?php echo htmlspecialchars($group['name']); ?>
            <a href="dashboard.php" style="float: right; color: white; text-decoration: none;">X</a>
        </div>
        <div id="chat-box">Učitavanje...</div>
        <form class="input-area" id="chat-form">
            <input type="text" id="msg-input" placeholder="Poruka grupi..." autocomplete="off">
            <button type="submit" style="background: #00adb5; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Pošalji</button>
        </form>
    </div>

    <div class="members-sidebar">
        <strong>ČLANOVI</strong>
        <div id="members-list" style="margin: 15px 0;">
            <?php
            $stmt_m = $pdo->prepare("SELECT u.username FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
            $stmt_m->execute([$group_id]);
            while($m = $stmt_m->fetch()) echo "<div class='member-name'>● " . htmlspecialchars($m['username']) . "</div>";
            ?>
        </div>

        <strong>DODAJ PRIJATELJA</strong>
        <form method="POST" style="margin-top: 10px;">
            <select name="add_member_id" onchange="this.form.submit()" style="width: 100%; font-size: 12px;">
                <option value="">Izaberi...</option>
                <?php
                // Samo prijatelji koji već nisu u grupi
                $stmt_p = $pdo->prepare("
                    SELECT u.id, u.username FROM users u 
                    JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) 
                    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted' AND u.id != ?
                    AND u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)
                ");
                $stmt_p->execute([$my_id, $my_id, $my_id, $group_id]);
                while($p = $stmt_p->fetch()) echo "<option value='".$p['id']."'>".$p['username']."</option>";
                ?>
            </select>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        function fetchMessages() {
            fetch(`group_chat.php?id=<?php echo $group_id; ?>&fetch=1&t=${Date.now()}`)
                .then(r => r.text()).then(data => { chatBox.innerHTML = data; });
        }
        document.getElementById('chat-form').onsubmit = (e) => {
            e.preventDefault();
            const input = document.getElementById('msg-input');
            if (!input.value.trim()) return;
            let fd = new FormData(); fd.append('msg', input.value);
            fetch(`group_chat.php?id=<?php echo $group_id; ?>`, { method: 'POST', body: fd })
                .then(() => { input.value = ''; fetchMessages(); chatBox.scrollTop = chatBox.scrollHeight; });
        };
        setInterval(fetchMessages, 2000); fetchMessages();
    </script>
</body>
</html>
