<?php
session_start();

if (!empty($_SESSION['logged_in'])) {
    header("Location: index.php");
    exit;
}

$error = "";

/* PASSWORD FILE */
$BASE_DIR = realpath(__DIR__ . "/..");
$passwordFile = $BASE_DIR . "/config/password.txt";

/* READ PASSWORD FROM FILE */
if (!file_exists($passwordFile)) {
    die("Password file not found!");
}

$valid_pass = trim(file_get_contents($passwordFile));

$valid_user = "admin"; // still fixed username

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /* simple brute force protection */
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    if ($_SESSION['login_attempts'] >= 5) {
        $error = "Too many failed attempts. Try again later.";
    } else {

        if ($username === $valid_user && $password === $valid_pass) {

            session_regenerate_id(true);

            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['last_activity'] = time();

            $_SESSION['login_attempts'] = 0;

            header("Location: index.php");
            exit;

        } else {
            $_SESSION['login_attempts']++;
            $error = "Invalid username or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="style.css">

<style>
body {
    background: radial-gradient(circle at top, #1f2937, #0b1220);
}

.login-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    width: 100%;
    max-width: 380px;
    background: rgba(17, 24, 39, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid #1f2937;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    text-align: center;
}

.login-box h2 {
    margin-bottom: 20px;
    font-size: 22px;
    color: #e5e7eb;
}

.login-box input {
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    border-radius: 10px;
    border: 1px solid #374151;
    background: #0f172a;
    color: #fff;
}

.login-box button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(90deg, #2563eb, #1d4ed8);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: bold;
}

.error {
    color: #f87171;
    margin-bottom: 10px;
    font-size: 13px;
}

.logo {
    display:block;
    margin:0 auto 15px;
    width:90px;
    height:90px;
    border-radius:50%;
    object-fit: contain;
}
</style>
</head>

<body>

<div class="login-container">
    <div class="login-box">

        <img src="img/mvnbc.png" class="logo" alt="MVNBC">

        <h2>Multivendor Network Backup Config</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>

    </div>
</div>

</body>
</html>
