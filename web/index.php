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
$backupDir = realpath(__DIR__ . "/../backups");
$logDir    = realpath(__DIR__ . "/../logs");
$search = strtolower(trim($_GET['search'] ?? ''));

/* BACKUP SCANNER */
function getBackupFilesGrouped($baseDir, $search = '')
{
    $vendors = glob($baseDir . '/*', GLOB_ONLYDIR) ?: [];
    $grouped = [];

    foreach ($vendors as $vendorPath) {

        $vendor = basename($vendorPath);
        $files = glob($vendorPath . '/*') ?: [];
        rsort($files);

        foreach ($files as $file) {

            if (!is_file($file)) continue;

            $filename = basename($file);

            preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $filename, $ipMatch);
            $ip = $ipMatch[1] ?? 'Unknown';

            $date = date("Y-m-d H:i:s", filemtime($file));

            $hay = strtolower("$vendor $ip $filename $date");

            if ($search && strpos($hay, $search) === false) continue;

            $grouped[$vendor][] = [
                "path" => $file,
                "name" => $filename,
                "ip"   => $ip,
                "size" => round(filesize($file)/1024,2),
                "date" => $date
            ];
        }
    }

    return $grouped;
}

/* LOG SCANNER */
function getLogs($dir) {
    $files = glob($dir."/*") ?: [];
    rsort($files);
    return $files;
}

$backupGroups = getBackupFilesGrouped($backupDir, $search);
$logFiles = getLogs($logDir);
?>

<!DOCTYPE html>
<html>
<head>
<title>NOC Backup Dashboard</title>
<link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="container">

<!-- TOP BAR -->
<div class="topbar">
    <h1>🚀 MVNBC Dashboard</h1>
    <a class="logout" href="logout.php">Logout</a>
</div>

<?php if (isset($_GET['deleted']) || isset($_GET['failed'])): ?>
<div style="margin:10px 0; padding:10px; background:#111827; border-radius:8px; color:#e5e7eb;">
    ✅ Deleted: <?= (int)($_GET['deleted'] ?? 0) ?> |
    ❌ Failed: <?= (int)($_GET['failed'] ?? 0) ?>
</div>
<?php endif; ?>

<hr>

<!-- STATS -->
<div class="stats-box">
    <h2>System Overview</h2>
    <div id="stats">Loading...</div>
</div>

<hr>

<!-- SEARCH -->
<form method="GET" class="search-box">
    <input name="search" placeholder="Search vendor / IP / date..." value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
    <a href="index.php">Clear</a>
</form>

<hr>

<!-- ================= BACKUP FILES ================= -->
<h2>Backup Files</h2>

<?php foreach ($backupGroups as $vendor => $files): ?>

<form method="POST" action="bulk_delete.php">

<input type="hidden" name="type" value="backup">

<div class="vendor-section">

<h3><?= strtoupper($vendor) ?></h3>

<table>
<tr>
    <th><input type="checkbox" onclick="toggleVendorAll(this, '<?= $vendor ?>')"></th>
    <th>File</th>
    <th>IP</th>
    <th>Size</th>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php foreach ($files as $f): ?>

<tr class="vendor-<?= $vendor ?>">
    <td>
        <input type="checkbox" name="files[]" value="<?= htmlspecialchars($f['path']) ?>">
    </td>
    <td><?= htmlspecialchars($f['name']) ?></td>
    <td><?= htmlspecialchars($f['ip']) ?></td>
    <td><?= $f['size'] ?> KB</td>
    <td><?= $f['date'] ?></td>
    <td>
        <a href="view.php?file=<?= urlencode($f['path']) ?>">View</a>
        <a href="download.php?file=<?= urlencode($f['path']) ?>">Download</a>
    </td>
</tr>

<?php endforeach; ?>

</table>

<br>

<button type="button" onclick="selectVendorAll('<?= $vendor ?>')">Select All</button>
<button type="button" onclick="unselectVendorAll('<?= $vendor ?>')">Unselect All</button>
<button type="submit" onclick="return confirm('Delete selected backup files?')">
Delete Selected
</button>

</div>

</form>

<hr>

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
    <td><?= round(filesize($l)/1024,2) ?> KB</td>
    <td><?= date("Y-m-d H:i:s", filemtime($l)) ?></td>
    <td>
        <a href="view.php?file=<?= urlencode($l) ?>">View</a>
        <a href="download.php?file=<?= urlencode($l) ?>">Download</a>
    </td>
</tr>

<?php endforeach; ?>

</table>

<br>

<button type="submit" onclick="return confirm('Delete selected logs?')">
Delete Selected Logs
</button>

</form>

</div>

<!-- ================= DASHBOARD JS ================= -->
<script>

async function loadStats(){

    const r = await fetch("api_stats.php");
    const d = await r.json();

    let html = "";

    html += `
    <div class="card-row">
        <div class="card green">Total<br><b>${d.total}</b></div>
        <div class="card blue">Success<br><b>${d.ok}</b></div>
        <div class="card red">Fail<br><b>${d.fail}</b></div>
    </div>

    <h3>Vendor Dashboard</h3>
    <div id="vendorWrap"></div>
    `;

    document.getElementById("stats").innerHTML = html;

    window.vendorData = d.vendors;
    renderVendors("all");
}

function renderVendors(filter){

    let html = "";

    window.vendorData.forEach(v => {

        let rate = v.count ? (v.ok / v.count) * 100 : 0;

        let state = "bad";
        let color = "#dc2626";

        if (rate >= 80) {
            state = "good";
            color = "#16a34a";
        } else if (rate >= 50) {
            state = "warn";
            color = "#f59e0b";
        }

        if (filter !== "all" && filter !== state) return;

        html += `
        <div class="vendor-card" style="border-left:5px solid ${color}">
            <b>${v.vendor.toUpperCase()}</b> <span style="color:${color}">${rate.toFixed(1)}%</span>
            <div>
                Total: ${v.count}<br>
                OK: ${v.ok}<br>
                FAIL: ${v.fail}<br>
                Last: ${v.last}
            </div>
        </div>
        `;
    });

    document.getElementById("vendorWrap").innerHTML = html;
}

/* ================= BULK TOOLS ================= */

function toggleVendorAll(master, vendor) {
    document.querySelectorAll(".vendor-" + vendor + " input[type=checkbox]")
        .forEach(cb => cb.checked = master.checked);
}

function selectVendorAll(vendor) {
    document.querySelectorAll(".vendor-" + vendor + " input[type=checkbox]")
        .forEach(cb => cb.checked = true);
}

function unselectVendorAll(vendor) {
    document.querySelectorAll(".vendor-" + vendor + " input[type=checkbox]")
        .forEach(cb => cb.checked = false);
}

function toggleAllLogs(master) {
    document.querySelectorAll("#logForm input[type=checkbox]")
        .forEach(cb => cb.checked = master.checked);
}

loadStats();
setInterval(loadStats, 10000);

</script>

</body>
</html>