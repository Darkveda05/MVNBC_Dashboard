<?php
session_start();
if (!isset($_SESSION['logged_in'])) die("Unauthorized");

$file = file_get_contents("../../../config/devices.conf");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Config Editor</title>
</head>

<body style="font-family:Arial;background:#0b1220;color:#e5e7eb;padding:20px;">

<!-- TOP BAR -->
<div style="display:flex;justify-content:space-between;align-items:center;background:#111827;padding:10px;border-radius:8px;">
    <h3>⚙️ Config Editor (VIew Only)</h3>

    <!-- BACK BUTTON -->
    <a href="../../index.php"
       style="background:#2563eb;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">
       ⬅ Back to Dashboard
    </a>
</div>

<br>

<!-- FORM -->
<form method="POST" action="../api/save_config.php">
    <textarea name="data" style="width:100%;height:500px;background:#111827;color:white;border:none;padding:10px;">
<?= htmlspecialchars($file) ?>
    </textarea>

    <br><br>
<!--
    <button type="submit" style="padding:10px 15px;background:#16a34a;color:white;border:none;border-radius:6px;">
        Save Config
    </button>
-->
<div style="padding:15px;border:1px solid #ddd;border-radius:8px;background:#111827;">
    <h4>Modify Devices Configuration</h4>

    <p>Open Terminal and execute the following command:</p>

    <pre style="background:#212529;color:#fff;padding:10px;border-radius:5px;">
    Path " MVNBC_Dashboard/config "<br></br>
    cd config 
    nano devices.conf

    Format:
    vendor,ip address,username,password,enable password
    cisco,172.16.30.29,admin,cisco,cisco123
    mikrotik,172.16.30.1,admin,admin123   <------- if no enable password, just let it blank
    </pre>

    <p style="color:#dc3545;margin-bottom:0;">
        <strong>Access Restricted:</strong> Only users with root administrator privileges can modify this file.
    </p>
</div>



</form>

</body>
</html>
