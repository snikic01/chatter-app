<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$query = $_GET['q'] ?? '';

// Napredniji upit: Tražimo korisnike i proveravamo status prijateljstva sa njima
$stmt = $pdo->prepare("
    SELECT u.id, u.username, f.status, f.user_id AS requester_id
    FROM users u
    LEFT JOIN friends f ON (
        (f.user_id = ? AND f.friend_id = u.id) OR 
        (f.user_id = u.id AND f.friend_id = ?)
    )
    WHERE u.username LIKE ? AND u.id != ?
");
$stmt->execute([$my_id, $my_id, "%$query%", $my_id]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Pretraga korisnika</title>
    <link rel="stylesheet" href="style.css"> <!-- Koristimo tvoj glavni CSS -->
    <style>
        /* Specifični stilovi za rezultate pretrage */
        .search-results { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .user-card { 
            background: var(--card-bg); 
            padding: 20px; 
            margin-bottom: 15px; 
            border: 1px solid var(--border); 
            border-radius: 12px;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .status-text { font-size: 12px; color: var(--text-muted); font-style: italic; }
    </style>
</head>
<body style="display: block; overflow-y: auto;"> <!-- Resetujemo flex layout iz style.css za ovu stranu -->

    <div class="search-results">
        <a href="dashboard.php" class="item-row" style="display: inline-block; margin-bottom: 20px; color: var(--accent);">← Nazad na Dashboard</a>
        
        <h2 style="color: var(--accent); margin-bottom: 25px;">Rezultati za: "<?php echo htmlspecialchars($query); ?>"</h2>

        <?php if (empty($users)): ?>
            <p style="color: var(--text-muted);">Nije pronađen nijedan korisnik sa tim imenom.</p>
        <?php endif; ?>

        <?php foreach ($users as $u): ?>
            <div class="user-card">
                <span style="font-weight: bold; font-size: 1.1rem;"><?php echo htmlspecialchars($u['username']); ?></span>
                
                <div class="actions">
                    <?php if ($u['status'] === null): ?>
                        <!-- Nema nikakve veze u bazi -->
                        <a href="add_friend.php?id=<?php echo $u['id']; ?>" class="btn-send" style="text-decoration:none; padding: 8px 15px; font-size: 12px;">DODAJ</a>
                    
                    <?php elseif ($u['status'] === 'pending'): ?>
                        <?php if ($u['requester_id'] == $my_id): ?>
                            <span class="status-text">Zahtev poslat...</span>
                        <?php else: ?>
                            <a href="accept_friend.php?id=<?php echo $u['id']; ?>" class="btn-send" style="text-decoration:none; padding: 8px 15px; font-size: 12px; background: var(--success);">PRIHVATI</a>
                        <?php endif; ?>
                    
                    <?php elseif ($u['status'] === 'accepted'): ?>
                        <span style="color: var(--success); font-weight: bold; font-size: 13px;">Prijatelji ✓</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
