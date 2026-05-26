<?php
session_start();

$USERNAME = "admin";
$PASSWORD = "admin123";

$maxAttempts = 5;
$lockTime = 300; // 5 minutes

// init
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
}

if (!isset($_SESSION['lock_time'])) {
    $_SESSION['lock_time'] = 0;
}

// check lock
if ($_SESSION['attempts'] >= $maxAttempts) {

    if (time() - $_SESSION['lock_time'] < $lockTime) {
        header("Location: login.php?error=locked");
        exit;
    } else {
        // reset after timeout
        $_SESSION['attempts'] = 0;
        $_SESSION['lock_time'] = 0;
    }
}

$user = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';

if ($user === $USERNAME && $pass === $PASSWORD) {

    $_SESSION['logged_in'] = true;
    $_SESSION['attempts'] = 0;

    header("Location: index.php");
    exit;
}

// failed login
$_SESSION['attempts']++;

if ($_SESSION['attempts'] >= $maxAttempts) {
    $_SESSION['lock_time'] = time();
}

header("Location: login.php?error=1");
exit;