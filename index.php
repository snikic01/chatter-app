<?php
session_start();
require_once 'db_chatter.php';

$error = "";

// REGISTRACIJA
if (isset($_POST['register'])) {
    $u = trim($_POST['username']);
    $p = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Pogrešni podaci.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chatter | Login</title>
    <link rel="stylesheet" href="../style.css"> <!-- Koristimo tvoj postojeci CSS -->
    <style>
        .auth-container { max-width: 350px; margin: 100px auto; background: white; padding: 20px; border: 1px solid #ccc; }
        .tab-btn { background: none; border: none; color: #0079D3; cursor: pointer; font-weight: bold; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        .btn-main { background: #0079D3; color: white; border: none; width: 100%; padding: 10px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2 id="form-title">Login na Chatter</h2>
        <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
        <?php if(isset($success)) echo "<p style='color:green'>$success</p>"; ?>

        <form method="POST" id="auth-form">
            <input type="text" name="username" placeholder="Korisničko ime" required>
            <input type="password" name="password" placeholder="Lozinka" required>
            <button type="submit" name="login" id="submit-btn" class="btn-main">Uloguj se</button>
        </form>
        
        <p style="font-size: 12px; text-align: center; margin-top: 15px;">
            Nemaš nalog? <button type="button" class="tab-btn" onclick="toggleForm()">Registruj se</button>
        </p>
    </div>

    <script>
        function toggleForm() {
            const title = document.getElementById('form-title');
            const btn = document.getElementById('submit-btn');
            if (btn.name === 'login') {
                title.innerText = 'Registracija';
                btn.name = 'register';
                btn.innerText = 'Napravi nalog';
            } else {
                title.innerText = 'Login na Chatter';
                btn.name = 'login';
                btn.innerText = 'Uloguj se';
            }
        }
    </script>
</body>
</html>
