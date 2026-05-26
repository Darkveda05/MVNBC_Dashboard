<?php

header("Content-Type: application/json");

/* 🚀 FORCE NO CACHE (IMPORTANT FOR REAL-TIME DASHBOARD) */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   PATHS
========================= */
$backupDir = realpath(__DIR__ . '/../backups');
$configFile = realpath(__DIR__ . '/../config/devices.conf');

/* =========================
   INIT
========================= */
$total = 0;
$ok = 0;
$fail = 0;
$vendors = [];

/* =========================
   READ DEVICES
========================= */
if (!file_exists($configFile)) {
    echo json_encode([
        "error" => "devices.conf not found"
    ]);
    exit;
}

$lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {

    $line = trim($line);

    if ($line === "" || $line[0] === "#") continue;

    $parts = explode(",", $line);

    if (count($parts) < 2) continue;

    $vendor = trim($parts[0]);
    $ip = trim($parts[1]);

    $vendorDir = $backupDir . "/" . $vendor;

    $status = "FAIL";
    $latestFile = null;

    /* =========================
       FIND LATEST BACKUP FILE
    ========================= */
    if (is_dir($vendorDir)) {

        $files = glob($vendorDir . "/*" . $ip . "*");

        if ($files && count($files) > 0) {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            $latestFile = $files[0];

            if (file_exists($latestFile) && filesize($latestFile) > 0) {
                $status = "OK";
            }
        }
    }

    /* =========================
       GLOBAL COUNTERS
    ========================= */
    $total++;

    if ($status === "OK") {
        $ok++;
    } else {
        $fail++;
    }

    /* =========================
       VENDOR GROUPING
    ========================= */
    if (!isset($vendors[$vendor])) {
        $vendors[$vendor] = [
            "vendor" => $vendor,
            "count" => 0,
            "ok" => 0,
            "fail" => 0,
            "last" => "-"
        ];
    }

    $vendors[$vendor]["count"]++;

    if ($status === "OK") {
        $vendors[$vendor]["ok"]++;
        $vendors[$vendor]["last"] = date("Y-m-d H:i:s", filemtime($latestFile));
    } else {
        $vendors[$vendor]["fail"]++;
    }
}

/* =========================
   OUTPUT JSON
========================= */
echo json_encode([
    "total" => $total,
    "ok" => $ok,
    "fail" => $fail,
    "vendors" => array_values($vendors)
]);

?>