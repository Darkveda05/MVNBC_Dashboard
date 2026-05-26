<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    die("Unauthorized");
}

/* =========================
   PATH SECURITY
========================= */
$allowedDirs = [
    realpath(__DIR__ . "/../backups"),
    realpath(__DIR__ . "/../logs")
];

$file = realpath($_GET['file'] ?? '');

$valid = false;

foreach ($allowedDirs as $dir) {
    if ($file && strpos($file, $dir) === 0) {
        $valid = true;
    }
}

if (!$valid || !file_exists($file)) {
    die("Invalid file");
}

/* =========================
   FILE CONTENT
========================= */
$content = htmlspecialchars(file_get_contents($file));
$filename = basename($file);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $filename ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="container">

<div class="topbar">
    <h1>File Viewer</h1>
    <a class="logout" href="index.php">Back</a>
</div>

<hr>

<h3><?= $filename ?></h3>

<pre>
<?= $content ?>
</pre>

</div>

</body>
</html>