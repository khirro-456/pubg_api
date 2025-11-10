<?php
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/csrf.php';

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("Error: Invalid CSRF token.");
    }

    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Login failed. Please check your username and password.";
    }
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PUBG Player Finder | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    body {
        min-height: 100vh;
        margin: 0;
        font-family: 'Montserrat', Arial, sans-serif;
        background: url('public/img/img-og-pubg.jpg') center/cover no-repeat fixed;
        display: flex;
        justify-content: center;
        align-items: center;
        color: #eaf6fa;
    }
    .overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(22,28,44, 0.57);
        z-index: 0;
    }
    .auth-container {
        position: relative;
        z-index: 1;
        max-width: 410px;
        width: 100%;
        background: rgba(28,38,70,0.67);
        border-radius: 18px;
        box-shadow: 0 6px 32px rgba(31,38,135,0.16), 0 1.5px 0 #fff5;
        backdrop-filter: blur(9px);
        padding: 45px 38px 35px 38px;
        color: #eaf6fa;
        text-align: center;
    }
    h2 {
        font-size: 2em;
        font-family: 'Orbitron', sans-serif;
        margin-bottom: 12px;
        color: #6edaff;
        letter-spacing: 1px;
        font-weight: 700;
        text-shadow: 0 2px 12px #14b3d944;
    }
    .intro-text {
        font-size: 1.08em;
        color: #c1eaff;
        margin-bottom: 24px;
        text-shadow: 0 1px 7px #0e224481;
    }
    .form-group {
        margin-bottom: 21px;
        position: relative;
        text-align: left;
    }
    label {
        display: block;
        margin-bottom: 7px;
        color: #aee2fa;
        font-size: 1em;
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    input[type="text"], input[type="password"] {
        width: 100%;
        font-size: 1.08em;
        border-radius: 7px;
        padding: 13px 14px;
        background: rgba(255,255,255,0.09);
        border: none;
        color: #fff;
        border-bottom: 2px solid #2196f3;
        box-sizing: border-box;
        outline: none;
    }
    input[type="text"]:focus, input[type="password"]:focus {
        border-bottom: 2.2px solid #00b894;
        background: rgba(255,255,255,0.13);
    }
    .auth-btn {
        margin-top: 8px;
        width: 100%;
        border: none;
        border-radius: 8px;
        padding: 15px 0;
        background: linear-gradient(90deg, #45bbff 40%, #187cff 100%);
        color: #fff;
        font-size: 1.15em;
        font-weight: 700;
        cursor: pointer;
        transition: background .17s;
        letter-spacing: 1px;
    }
    .auth-btn:hover {
        background: linear-gradient(90deg,#38a4e6,#075eea);
    }
    .auth-link {
        display: block;
        text-align: center;
        margin-top: 21px;
        color: #42cefd;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.02em;
    }
    .auth-link:hover {
        text-decoration: underline;
        color: #38e6f2;
    }
    .message {
        font-size: 16px;
        text-align: center;
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 8px;
        background: #12458e22;
        color: #edeef7;
    }
    .error { background: #e84f4f26; color: #ffdfdf;}
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="auth-container">
        <h2>PUBG Player Finder</h2>
        <div class="intro-text">
            Log in to <b>find player stats</b>, search top battleground legends, and get the edge in every match!
        </div>
        <?php if (!empty($error)) echo "<div class='message error'>$error</div>"; ?>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="form-group">
                <label for="username">PUBG Username</label>
                <input type="text" id="username" name="username" required maxlength="32">
            </div>
            <div class="form-group">
                <label for="password">Account Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <button class="auth-btn" type="submit">Sign In</button>
        </form>
        <a class="auth-link" href="register.php">No account? Register for Player Finder</a>
    </div>
</body>
</html>
