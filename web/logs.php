<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

/* SESSION TIMEOUT */
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

if (time() - $_SESSION['last_activity'] > 900) {
    session_destroy();
    header("Location: login.php?error=session");
    exit;
}

$_SESSION['last_activity'] = time();
clearstatcache(true);

/* DIRECTORIES */
$logDir = __DIR__ . "/../logs";

/* SAFE LOG LOADER */
function getLogs($dir) {
    if (!is_dir($dir)) return [];

    $files = glob($dir . "/*") ?: [];
    $files = array_filter($files, 'is_file');
    rsort($files);
    return $files;
}

$logFiles = getLogs($logDir);

/* TEMP FIX: backupGroups not defined */
$backupGroups = [];
?>

<!DOCTYPE html>
<html>
<head>
<title>Logs</title>
<link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="container">

<!-- TOP BAR -->
<div class="ul">
    <h1>🚀 MVNBC Dashboard</h1>
    <a class="logout" href="index.php">Dashboard</a>
    <a class="logout" href="app/modules/compare.php">Compare</a>
    <a class="logout" href="logs.php">Logs</a>
    <a class="logout" href="app/modules/bash.php">Bash</a>
    <a class="logout" href="app/modules/config.php">Devices</a>
    <a class="logout" href="app/modules/settings.php">Change Password</a>
    <a class="logout" href="logout.php">Logout</a>
</div>

<?php if (isset($_GET['deleted']) || isset($_GET['failed'])): ?>
<div style="margin:10px 0; padding:10px; background:#111827; border-radius:8px; color:#e5e7eb;">
    ✅ Deleted: <?= (int)($_GET['deleted'] ?? 0) ?> |
    ❌ Failed: <?= (int)($_GET['failed'] ?? 0) ?>
</div>
<?php endif; ?>

<hr>

<!-- ================= BACKUP FILES (SAFE EMPTY BLOCK) ================= -->
<?php foreach ($backupGroups as $vendor => $files): ?>
<?php endforeach; ?>

<!-- ================= LOG FILES ================= -->
<h2>Logs</h2>

<form method="POST" action="bulk_delete.php" id="logForm">

<input type="hidden" name="type" value="log">

<table>
<tr>
    <th><input type="checkbox" onclick="toggleAllLogs(this)"></th>
    <th>Name</th>
    <th>Size</th>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php foreach ($logFiles as $l): ?>
<tr>
    <td>
        <input type="checkbox" name="files[]" value="<?= htmlspecialchars($l) ?>">
    </td>

    <td><?= basename($l) ?></td>

    <td>
        <?= file_exists($l) ? round(filesize($l)/1024, 2) : 0 ?> KB
    </td>

    <td>
        <?= file_exists($l) ? date("Y-m-d H:i:s", filemtime($l)) : '-' ?>
    </td>

    <td>
        <a href="view.php?file=<?= urlencode($l) ?>">View</a>
        <a href="download.php?file=<?= urlencode($l) ?>">Download</a>
    </td>
</tr>
<?php endforeach; ?>

</table>

<br>

<!-- ✅ SELECT ALL / UNSELECT ALL BUTTONS -->
<button type="button" onclick="selectAllLogs(true)">Select All</button>
<button type="button" onclick="selectAllLogs(false)">Unselect All</button>

<button type="submit" onclick="return confirm('Delete selected logs?')">
Delete Selected
</button>

</form>

</div>

<!-- ================= JS ================= -->
<script>
function selectAllLogs(state) {
    document.querySelectorAll('#logForm input[type=checkbox]').forEach(cb => {
        cb.checked = state;
    });
}

function toggleAllLogs(source) {
    document.querySelectorAll('#logForm input[type=checkbox]').forEach(cb => {
        cb.checked = source.checked;
    });
}
</script>

</body>
</html>
