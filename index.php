<?php
session_start();
require_once 'db_chatter.php';

$error = "";
$success = "";

// REGISTRACIJA
if (isset($_POST['register'])) {
    $u = trim($_POST['username']);
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Podrazumevano, is_banned je 0 (nije banovan)
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, is_banned) VALUES (?, ?, 0)");
        $stmt->execute([$u, $p]);
        $success = "Uspešna registracija! Sad se uloguj.";
    } catch (Exception $e) {
        $error = "Korisničko ime je zauzeto.";
    }
}

// LOGIN
if (isset($_POST['login'])) {
    $u = trim($_POST['username']);
    $p = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch();

    if ($user && password_verify($p, $user['password_hash'])) {
        
        // --- PROVERA BANA ---
        if (isset($user['is_banned']) && $user['is_banned'] == 1) {
            $error = "Pristup odbijen. Vaš nalog je suspendovan.";
        } else {
            // Ako nije banovan, nastavi sa logovanjem
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Beleženje IP adrese u login_logs
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $logStmt = $pdo->prepare("INSERT INTO login_logs (username, ip_address) VALUES (?, ?)");
            $logStmt->execute([$user['username'], $ip]);

            header("Location: dashboard.php");
            exit();
        }
        
    } else {
        $error = "Pogrešni podaci.";
    }
}

?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Chatter | Login & Registracija</title>
    <link rel="stylesheet" href="style.css"> <!-- Putanja bez ../ jer je u istom folderu -->
    <style>
        .auth-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
            background: var(--bg-dark);
        }
        .auth-container {
            width: 100%;
            max-width: 400px;
            background: var(--sidebar-bg);
            padding: 40px;
            border-radius: 15px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-header h2 {
            color: var(--accent);
            margin: 0;
            font-size: 1.8rem;
        }
        .back-to-landing {
            position: absolute;
            top: -50px;
            left: 0;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .back-to-landing:hover {
            color: var(--accent);
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        .alert-error { background: rgba(255, 77, 77, 0.1); color: var(--danger); border: 1px solid var(--danger); }
        .alert-success { background: rgba(70, 209, 96, 0.1); color: var(--success); border: 1px solid var(--success); }
        
        .tab-switcher {
            text-align: center;
            margin-top: 20px;
            color: var(--text-muted);
            font-size: 14px;
        }
        .tab-btn {
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            font-weight: bold;
            padding: 0;
            font-size: 14px;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-container">
            <!-- Dugme za nazad na landing page (index.html je jedan nivo iznad) -->
            <a href="../index.html" class="back-to-landing">← Nazad na početnu</a>

            <div class="auth-header">
                <h2 id="form-title">Login</h2>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" id="auth-form">
                <input type="text" name="username" class="modern-input" placeholder="Korisničko ime" required>
                <input type="password" name="password" class="modern-input" placeholder="Lozinka" required>
                <button type="submit" name="login" id="submit-btn" class="btn-send" style="margin-top: 10px;">Uloguj se</button>
            </form>

            <div class="tab-switcher">
                <span id="switch-text">Nemaš nalog?</span> 
                <button type="button" class="tab-btn" id="toggle-link" onclick="toggleForm()">Registruj se</button>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            const title = document.getElementById('form-title');
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('switch-text');
            const link = document.getElementById('toggle-link');

            if (btn.name === 'login') {
                title.innerText = 'Registracija';
                btn.name = 'register';
                btn.innerText = 'Napravi nalog';
                text.innerText = 'Već imaš nalog?';
                link.innerText = 'Uloguj se';
            } else {
                title.innerText = 'Login';
                btn.name = 'login';
                btn.innerText = 'Uloguj se';
                text.innerText = 'Nemaš nalog?';
                link.innerText = 'Registruj se';
            }
        }
    </script>
</body>
</html>
