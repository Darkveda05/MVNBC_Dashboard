<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = "";

// Demo login (replace with DB later)
$valid_user = "admin";
$valid_pass = "admin";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $valid_user && $password === $valid_pass) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;

        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password";
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
/* ===== LOGIN BACKGROUND ===== */
body {
    background: radial-gradient(circle at top, #1f2937, #0b1220);
}

/* ===== CENTER WRAPPER ===== */
.login-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ===== LOGIN CARD ===== */
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

/* TITLE */
.login-box h2 {
    margin-bottom: 20px;
    font-size: 22px;
    color: #e5e7eb;
}

/* INPUTS */
.login-box input {
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    border-radius: 10px;
    border: 1px solid #374151;
    background: #0f172a;
    color: #fff;
    outline: none;
}

.login-box input:focus {
    border-color: #3b82f6;
}

/* BUTTON */
.login-box button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(90deg, #2563eb, #1d4ed8);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

.login-box button:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(37,99,235,0.3);
}

/* ERROR */
.error {
    color: #f87171;
    margin-bottom: 10px;
    font-size: 13px;
}

/*logo */
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
         <img src="img/mvnbc.png" alt="MVNBC" class="logo">
        <h2>Multivendor Network Backup Config</h2>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
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
