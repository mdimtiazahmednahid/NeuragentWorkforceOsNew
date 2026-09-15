<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>System Diagnostic - Log Reader</h2>";

$log_file = __DIR__ . '/../storage/logs/laravel.log';

if (!file_exists($log_file)) {
    echo "<p style='color:orange;'>⚠️ No log file found at: " . $log_file . "</p>";
} else {
    // Read the last 100 lines of the log file
    $lines = file($log_file);
    if ($lines === false) {
        echo "<p style='color:red;'>❌ Could not read the log file. Permissions issue?</p>";
    } else {
        $last_lines = array_slice($lines, -150);
        echo "<p style='color:green;'>✅ Log file loaded! Here are the most recent errors:</p>";
        echo "<pre style='background:#f4f4f4; padding:15px; border:1px solid #ccc; overflow-x:scroll;'>";
        foreach ($last_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    }
}
