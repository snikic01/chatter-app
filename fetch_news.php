<?php
session_start();
require_once 'db_chatter.php';

if (!isset($_SESSION['user_id'])) exit();

$my_id = $_SESSION['user_id'];
// Proveravamo da li je korisnik admin
$is_admin = ($_SESSION['username'] === 'snikic01');

$news = $pdo->query("SELECT * FROM admin_news ORDER BY created_at DESC")->fetchAll();

foreach ($news as $n): 
    // Podaci za Lajkove
    $likes = $pdo->prepare("SELECT COUNT(*) FROM news_likes WHERE news_id = ?");
    $likes->execute([$n['id']]);
    $l_count = $likes->fetchColumn();

    $my_l = $pdo->prepare("SELECT id FROM news_likes WHERE news_id = ? AND user_id = ?");
    $my_l->execute([$n['id'], $my_id]);
    $liked = $my_l->fetch();

    // Podaci za Komentare
    $comm_stmt = $pdo->prepare("SELECT nc.*, u.username FROM news_comments nc JOIN users u ON nc.user_id = u.id WHERE nc.news_id = ? ORDER BY nc.created_at ASC");
    $comm_stmt->execute([$n['id']]);
    $comments = $comm_stmt->fetchAll();
?>
    <div class="news-card <?= ($n['type'] === 'urgent') ? 'urgent' : '' ?>">
        <span class="admin-badge" style="background: <?= ($n['type'] === 'urgent') ? 'var(--danger)' : 'var(--accent)' ?>;">ADMIN</span>
        
        <div style="margin-bottom: 10px;">
            <small style="color: var(--text-muted);">Snikic • <?= date("H:i | d.m.Y", strtotime($n['created_at'])) ?></small>
        </div>
        
        <h2 style="margin: 0 0 10px 0; color: #fff;"> <?= htmlspecialchars($n['title']) ?> </h2>
        <div style="color: #bbb; white-space: pre-wrap; margin-bottom:15px;"><?= htmlspecialchars($n['content']) ?></div>
        
        <div class="news-footer">
            <div class="interaction-bar">
                <!-- Lajk dugme -->
                <button onclick="toggleLike(<?= $n['id'] ?>)" class="btn-like <?= $liked ? 'active' : '' ?>">
                    <?= $liked ? '❤️' : '🤍' ?> <?= $l_count ?>
                </button>
                
                <span style="font-size:12px; color:var(--text-muted);">💬 <?= count($comments) ?></span>

                <!-- VRACENA ADMIN DUGMAD -->
                <?php if ($is_admin): ?>
                    <div style="margin-left:auto; display: flex; gap: 10px;">
                        <a href="dashboard.php?edit_news=<?= $n['id'] ?>" class="btn-mini btn-edit">Edit</a>
                        <a href="dashboard.php?delete_news=<?= $n['id'] ?>" class="btn-mini btn-delete" onclick="return confirm('Obrisati?')">Del</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="comments-section">
                <?php foreach($comments as $c): ?>
                    <div class="comment-item">
                        <div class="comment-content">
                            <span class="comment-user"><?= htmlspecialchars($c['username']) ?>:</span>
                            <?= htmlspecialchars($c['comment_text']) ?>
                        </div>
                        <?php if($is_admin): ?>
                            <a href="dashboard.php?delete_comment=<?= $c['id'] ?>" class="del-com" onclick="return confirm('Obrisati komentar?')">✕</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="comment-input-group">
                    <input type="text" id="comm-txt-<?= $n['id'] ?>" placeholder="Napiši komentar...">
                    <button onclick="sendComment(<?= $n['id'] ?>)" class="btn-send" style="min-width:50px; height:30px; font-size:10px;">OK</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
