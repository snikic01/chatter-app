<?php
session_start();
require_once 'db_chatter.php';

// Ako korisnik nije ulogovan, šutni ga na login
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
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: #1a1a1a; color: #eee; display: flex; height: 100vh; margin: 0; }
        
        /* Sidebar za listu ljudi i grupa */
        .sidebar { 
            width: 300px; 
            background: #252525; 
            border-right: 1px solid #333; 
            display: flex; 
            flex-direction: column; 
        }
        
        .sidebar-header { padding: 20px; background: #00adb5; color: white; font-weight: bold; }
        
        .user-section { padding: 15px; border-bottom: 1px solid #333; }
        
        /* Glavni deo za čet */
        .main-chat { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        
        .empty-state { text-align: center; color: #555; }
        
        .btn-logout { 
            display: block; 
            padding: 10px; 
            color: #ff4d4d; 
            text-decoration: none; 
            font-size: 13px; 
            margin-top: auto;
        }
        
        .search-box { padding: 10px; }
        .search-box input { 
            width: 100%; 
            padding: 8px; 
            background: #333; 
            border: 1px solid #444; 
            color: white; 
            border-radius: 4px;
        }
    </style>
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
                <input type="text" name="q" placeholder="Pronađi prijatelje...">
            </form>
        </div>

        <div style="padding: 20px; color: #555; font-size: 14px;">
            <div style="padding: 10px;">
    <small style="color: #777;">MOJI PRIJATELJI</small>
    <?php
    // Vučemo ljude koji su prihvatili zahtev ili kojima smo mi prihvatili
    $stmt = $pdo->prepare("
        SELECT u.username, u.id 
        FROM users u 
        JOIN friends f ON (u.id = f.friend_id OR u.id = f.user_id) 
        WHERE (f.user_id = ? OR f.friend_id = ?) 
        AND u.id != ? 
        AND f.status = 'accepted'
    ");
    $stmt->execute([$my_id, $my_id, $my_id]);
    $friends = $stmt->fetchAll();

    foreach ($friends as $f) {
        echo "<div style='padding: 10px 0; border-bottom: 1px solid #333;'>";
        echo "<a href='chat.php?user_id=" . $f['id'] . "' style='color: white; text-decoration: none;'>● " . htmlspecialchars($f['username']) . "</a>";
        echo "</div>";
    }
    ?>
</div>

        </div>

        <a href="logout.php" class="btn-logout">Odjavi se</a>
    </div>

    <div class="main-chat">
        <div class="empty-state">
            <h1>Zdravo, <?php echo htmlspecialchars($my_user); ?>! 👋</h1>
            <p>Izaberi prijatelja iz liste ili kreiraj novu grupu da počneš četovanje.</p>
        </div>
    </div>

</body>
</html>
