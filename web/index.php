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


function validateBackupFile($vendor, $file)
{
    if (!file_exists($file) || filesize($file) == 0) {
        return false;
    }

    $content = @file_get_contents($file);
	

    switch (strtolower($vendor)) {

        case 'cisco':
            return preg_match(
                '/Current configuration|Building configuration|hostname/i',
                $content
            );

        case 'ciscoasa':
            return preg_match(
                '/ASA Version|hostname/i',
                $content
            );

        case 'mikrotik':
            return (
                stripos($content, 'RouterOS') !== false &&
                (
                    stripos($content, '/interface') !== false ||
                    stripos($content, '/ip ') !== false ||
                    stripos($content, '/system ') !== false
                )
            );

        case 'h3c':
            return preg_match('/sysname/i', $content);
			
        case 'h3c_1920':
            return preg_match('/sysname/i', $content);	

        case 'arista':
            return preg_match('/hostname/i', $content);

        case 'aruba':
            return preg_match('/hostname|Current configuration/i', $content);

        default:
            return filesize($file) > 0;
    }
}


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

            $status = validateBackupFile($vendor, $file);

			$grouped[$vendor][] = [
			"path" => $file,
			"name" => $filename,
			"ip"   => $ip,
			"size" => round(filesize($file)/1024,2),
			"date" => $date,
			"status" => $status
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
<title>MVNBC Dashboard</title>
<link rel="stylesheet" href="assets/style.css?v=2">
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

<h2 onclick="toggleBackupFiles()" style="cursor:pointer;">
    <span id="backupArrow">▼</span>
    Backup Files
</h2>

<div id="backupSection">

<?php foreach ($backupGroups as $vendor => $files): ?>

<form method="POST" action="bulk_delete.php">

<input type="hidden" name="type" value="backup">

<div class="vendor-section">

<h3 class="vendor-title"
    onclick="toggleVendorSection('<?= $vendor ?>')">

    <span id="arrow-<?= $vendor ?>">▼</span>

    <?= strtoupper($vendor) ?>
</h3>

<div id="vendor-<?= $vendor ?>">
<div id="backupSection">
<table>
<tr>
    <th><input type="checkbox" onclick="toggleVendorAll(this, '<?= $vendor ?>')"></th>
    <th>File</th>
    <th>IP</th>
    <th>Size</th>
    <th>Date</th>
	<th>Status</th>
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
	<?php if ($f['status']): ?>
		<span style="color:#22c55e;font-weight:bold;">OK</span>
	<?php else: ?>
		<span style="color:#ef4444;font-weight:bold;">FAILED</span>
	<?php endif; ?>
</td>
    <td>
        <a href="view2.php?file=<?= urlencode($f['path']) ?>">View</a>
        <a href="download.php?file=<?= urlencode($f['path']) ?>">Download</a>
    </td>
</tr>

<?php endforeach; ?>

</table>
</div>
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

</div>

<!-- ================= DASHBOARD JS ================= -->
<script>

async function loadStats(){

    const r = await fetch("api_stats.php?v=" + Date.now());
    const d = await r.json();

    let html = "";

html += `

<div class="dashboard-highlight">

    <div class="dashboard-title">
        Current Device Status
    </div>

    <div class="neo-row">

        <div class="neo-card green">
            <div class="neo-icon">🖥️</div>
            <div class="neo-content">
                <div class="neo-title">Total Devices</div>
                <div class="neo-value">${d.total}</div>
            </div>
        </div>

        <div class="neo-card blue">
            <div class="neo-icon">✅</div>
            <div class="neo-content">
                <div class="neo-title">Success Devices</div>
                <div class="neo-value">${d.ok}</div>
            </div>
        </div>

        <div class="neo-card red">
            <div class="neo-icon">❌</div>
            <div class="neo-content">
                <div class="neo-title">Failed Devices</div>
                <div class="neo-value">${d.fail}</div>
            </div>
        </div>

    </div>

</div>


<div class="dashboard-highlight">

    <div class="dashboard-title">
        Backup History
    </div>

    <div class="neo-row">

        <div class="neo-card green">
            <div class="neo-icon">📄</div>
            <div class="neo-content">
                <div class="neo-title">Total Files</div>
                <div class="neo-value">${d.file_total}</div>
            </div>
        </div>

        <div class="neo-card blue">
            <div class="neo-icon">✅</div>
            <div class="neo-content">
                <div class="neo-title">Successful Files</div>
                <div class="neo-value">${d.file_ok}</div>
            </div>
        </div>

        <div class="neo-card red">
            <div class="neo-icon">❌</div>
            <div class="neo-content">
                <div class="neo-title">Failed Files</div>
                <div class="neo-value">${d.file_fail}</div>
            </div>
        </div>

    </div>

</div>

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
        <div class="vendor-card"
			style="border-left:5px solid ${color}">

			<div class="vendor-card-header"
				 onclick="toggleVendorCard('${v.vendor}')">

			<span id="arrow-${v.vendor}">
				▼
			</span>

			<b>${v.vendor.toUpperCase()}</b>

			<span style="color:${color}">
				${rate.toFixed(1)}%
			</span>
			</div>

		<div id="body-${v.vendor}" class="vendor-card-body">

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

let vendorOpen = true;

function toggleVendorDashboard()
{
    const section = document.getElementById("vendorSection");
    const arrow = document.getElementById("vendorArrow");

    vendorOpen = !vendorOpen;

    if (vendorOpen)
    {
        section.style.maxHeight = "2000px";
        section.style.opacity = "1";
        arrow.innerHTML = "▼";
    }
    else
    {
        section.style.maxHeight = "0px";
        section.style.opacity = "0";
        arrow.innerHTML = "▶";
    }
}

function toggleVendorCard(vendor)
{
    const body =
        document.getElementById("body-" + vendor);

    const arrow =
        document.getElementById("arrow-" + vendor);

    if (body.style.display === "none")
    {
        body.style.display = "block";
        arrow.innerHTML = "▼";
    }
    else
    {
        body.style.display = "none";
        arrow.innerHTML = "▶";
    }
}

loadStats();
setInterval(loadStats, 10000);

</script>

<script>

let backupOpen = true;

function toggleBackupFiles()
{
    const section =
        document.getElementById("backupSection");

    const arrow =
        document.getElementById("backupArrow");

    backupOpen = !backupOpen;

    if (backupOpen)
    {
        section.style.display = "block";
        arrow.innerHTML = "▼";
    }
    else
    {
        section.style.display = "none";
        arrow.innerHTML = "▶";
    }
}

function toggleVendorSection(vendor)
{
    const section =
        document.getElementById("vendor-" + vendor);

    const arrow =
        document.getElementById("arrow-" + vendor);

    if (section.style.display === "none")
    {
        section.style.display = "block";
        arrow.innerHTML = "▼";
    }
    else
    {
        section.style.display = "none";
        arrow.innerHTML = "▶";
    }
}

</script>

</body>

</body>
</html>
