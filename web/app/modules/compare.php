<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: ../../login.php");
    exit;
}

/* =========================
   ROOT PATH
========================= */
define("ROOT_PATH", realpath(__DIR__ . "/../../.."));

/* =========================
   BACKUP ROOT (ALL VENDORS)
========================= */
$backupDir = ROOT_PATH . "/backups";

/* =========================
   GET ALL FILES (ALL VENDORS)
========================= */
function getAllFiles($dir)
{
    $vendors = glob($dir . "/*", GLOB_ONLYDIR);
    $all = array();

    if ($vendors) {
        foreach ($vendors as $vendorPath) {

            $files = glob($vendorPath . "/*");

            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) {
                        $all[] = $f;
                    }
                }
            }
        }
    }

    rsort($all);
    return $all;
}

/* =========================
   GET VENDOR NAME
========================= */
function getVendor($filePath)
{
    $parts = explode(DIRECTORY_SEPARATOR, $filePath);
    return isset($parts[count($parts) - 2]) ? $parts[count($parts) - 2] : 'UNKNOWN';
}

/* =========================
   LOAD FILES
========================= */
$files = getAllFiles($backupDir);

/* =========================
   INPUT
========================= */
$f1 = isset($_POST['file1']) ? $_POST['file1'] : '';
$f2 = isset($_POST['file2']) ? $_POST['file2'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Config Compare</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>

<body style="font-family:Arial;background:#0b1220;color:#e5e7eb;padding:20px;">

<!-- TOP BAR -->
<div style="display:flex;justify-content:space-between;align-items:center;background:#111827;padding:10px;border-radius:8px;">
    <h3>⚙️ Config Compare</h3>

    <a href="../../index.php"
       style="background:#2563eb;color:white;padding:8px 12px;border-radius:6px;text-decoration:none;">
       ⬅ Back to Dashboard
    </a>
</div>

<hr>

<h3>Select Two Config Files</h3>

<form method="POST">

<label>Old Config</label><br>
<select name="file1" required>
    <option value="">Select file</option>
    <?php foreach ($files as $f): ?>
        <option value="<?php echo htmlspecialchars($f); ?>">
            <?php echo getVendor($f) . " / " . basename($f); ?>
        </option>
    <?php endforeach; ?>
</select>

<br><br>

<label>New Config</label><br>
<select name="file2" required>
    <option value="">Select file</option>
    <?php foreach ($files as $f): ?>
        <option value="<?php echo htmlspecialchars($f); ?>">
            <?php echo getVendor($f) . " / " . basename($f); ?>
        </option>
    <?php endforeach; ?>
</select>

<br><br>

<button type="submit">Compare</button>

</form>

<hr>

<?php if ($f1 && $f2 && file_exists($f1) && file_exists($f2)): ?>

<?php
$c1 = file($f1, FILE_IGNORE_NEW_LINES);
$c2 = file($f2, FILE_IGNORE_NEW_LINES);
?>

<div style="background:#111;padding:10px;border-radius:6px;">
<b>VENDOR A:</b> <?php echo getVendor($f1); ?><br>
<b>FILE A:</b> <?php echo basename($f1); ?><br><br>

<b>VENDOR B:</b> <?php echo getVendor($f2); ?><br>
<b>FILE B:</b> <?php echo basename($f2); ?>
</div>

<hr>

<h3>DIFF RESULT</h3>

<pre style="background:#0f172a;padding:10px;border-radius:6px;">

=== ADDED (NEW) ===
<?php
foreach ($c2 as $line) {
    if (!in_array($line, $c1)) {
        echo "+ " . htmlspecialchars($line) . "\n";
    }
}
?>

=== REMOVED (OLD) ===
<?php
foreach ($c1 as $line) {
    if (!in_array($line, $c2)) {
        echo "- " . htmlspecialchars($line) . "\n";
    }
}
?>

</pre>

<?php endif; ?>

</body>
</html>
