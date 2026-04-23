<?php
session_start();
require_once 'db_chatter.php';
if (!isset($_SESSION['user_id'])) exit();

$my_id = $_SESSION['user_id'];
$total_unread = 0; // Na početku je nula

// --- ZAHTEVI ---
$stmt_req = $pdo->prepare("SELECT u.username, u.id FROM users u JOIN friends f ON u.id = f.user_id WHERE f.friend_id = ? AND f.status = 'pending'");
$stmt_req->execute([$my_id]);
$requests = $stmt_req->fetchAll();
if (count($requests) > 0) {
    echo '<div class="section-title">Zahtevi</div>';
    foreach ($requests as $r) {
        echo "<div class='item-row' style='background: rgba(70, 209, 96, 0.1);'>
                <span>" . htmlspecialchars($r['username']) . "</span>
                <a href='accept_friend.php?id={$r['id']}' style='color: var(--success); font-weight: bold; text-decoration: none;'>[✓]</a>
              </div>";
    }
}

// --- PRIJATELJI ---
echo '<div class="section-title">Prijatelji</div>';
$stmt = $pdo->prepare("SELECT u.username, u.id, u.last_seen FROM users u JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) WHERE (f.user_id = ? OR f.friend_id = ?) AND u.id != ? AND f.status = 'accepted'");
$stmt->execute([$my_id, $my_id, $my_id]);

while($f = $stmt->fetch()) {
    $is_online = (strtotime($f['last_seen']) > (time() - 300));
    $dot_color = $is_online ? 'var(--success)' : 'var(--text-muted)';
    $status_text = $is_online ? "" : "<small style='font-size:9px; color:var(--text-muted); margin-left:5px;'>" . time_ago($f['last_seen']) . "</small>";
    
    $st_u = $pdo->prepare("SELECT COUNT(*) FROM private_messages WHERE sender_id = ? AND receiver_id = ? AND seen = 0 AND group_id IS NULL");
    $st_u->execute([$f['id'], $my_id]);
    $count = $st_u->fetchColumn();
    
    $total_unread += $count; // DODAJEMO U UKUPAN ZBIR

    echo "<a href='chat.php?user_id={$f['id']}' class='item-row'>
            <span><span style='color: $dot_color; margin-right: 5px;'>●</span>" . htmlspecialchars($f['username']) . "$status_text</span>";
    if($count > 0) echo "<span class='badge'>$count</span>";
    echo "</a>";
}

// --- GRUPE ---
echo '<div class="section-title">Grupe</div>';
$stmt_g = $pdo->prepare("SELECT g.* FROM chat_groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
$stmt_g->execute([$my_id]);

while($g = $stmt_g->fetch()) {
    $st_ug = $pdo->prepare("
        SELECT COUNT(*) FROM private_messages pm 
        WHERE pm.group_id = ? AND pm.sender_id != ? 
        AND pm.id NOT IN (SELECT message_id FROM group_message_seen WHERE user_id = ?)
    ");
    $st_ug->execute([$g['id'], $my_id, $my_id]);
    $g_unread = $st_ug->fetchColumn();
    
    $total_unread += $g_unread; // DODAJEMO U UKUPAN ZBIR

    echo "<a href='group_chat.php?id={$g['id']}' class='item-row' style='color: var(--group-gold);'>
            <span><span style='color: var(--group-gold); margin-right: 5px;'>#</span>" . htmlspecialchars($g['name']) . "</span>";
    if($g_unread > 0) echo "<span class='badge' style='background: var(--group-gold); color: black;'>$g_unread</span>";
    echo "</a>";
}

// --- KLJUČNA LINIJA ZA ZVUK ---
// Sada ispisujemo zbir koji je JavaScript-u potreban
echo "<div id='total-unread-count' style='display:none;'>$total_unread</div>";
?>
