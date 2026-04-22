<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id'])) exit;

$my_id = $_SESSION['user_id'];
$query = $_GET['q'] ?? '';

// Tražimo korisnike čije ime sadrži pojam, a da to nismo mi
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE ? AND id != ?");
$stmt->execute(["%$query%", $my_id]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pretraga</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: #1a1a1a; color: white; padding: 20px; font-family: sans-serif; }
        .user-row { background: #252525; padding: 15px; margin-bottom: 10px; border: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
        .add-btn { background: #00adb5; color: white; border: none; padding: 5px 15px; cursor: pointer; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>
    <h2>Rezultati pretrage za: "<?php echo htmlspecialchars($query); ?>"</h2>
    <a href="dashboard.php" style="color: #00adb5;">← Nazad</a><br><br>

    <?php foreach ($users as $u): ?>
        <div class="user-row">
            <span><?php echo htmlspecialchars($u['username']); ?></span>
            <a href="add_friend.php?id=<?php echo $u['id']; ?>" class="add-btn">Dodaj prijatelja</a>
        </div>
    <?php endforeach; ?>
</body>
</html>
