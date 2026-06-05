<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: ../../login.php");
    exit;
}

$msg = "";

$BASE_DIR = realpath(__DIR__ . "/../../..");
$passwordFile = $BASE_DIR . "/config/password.txt";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old = isset($_POST['old_password']) ? $_POST['old_password'] : '';
    $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';

    $current = trim(file_get_contents($passwordFile));

    if ($old === $current) {

        file_put_contents($passwordFile, $new);
        $msg = "Password updated successfully!";

    } else {
        $msg = "Old password incorrect!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
</head>
<body style="font-family:Arial;background:#0b1220;color:#e5e7eb;padding:20px;">

<!-- TOP BAR -->
<div style="display:flex;justify-content:space-between;align-items:center;background:#111827;padding:10px;border-radius:8px;">
   <h3>⚙️  Settings</h3>

    <!-- BACK BUTTON -->
    <a href="../../index.php"
       style="background:#2563eb;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">
       ⬅ Back to Dashboard
    </a>
</div>

<h2>🔐 Change Password</h2>

<?php if ($msg) echo "<p>$msg</p>"; ?>

<form method="POST">
    <input type="password" name="old_password" placeholder="Old Password"><br><br>
    <input type="password" name="new_password" placeholder="New Password"><br><br>
    <button>Update</button>
</form>

</body>
</html>
