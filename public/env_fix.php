<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Laravel .env Configuration</h2>";

$env_path = dirname(__DIR__) . '/.env';

if (!file_exists($env_path)) {
    echo "<p style='color:red;'>❌ .env file not found at $env_path</p>";
    exit;
}

$env_content = file_get_contents($env_path);

// Replace SESSION_DRIVER
if (strpos($env_content, 'SESSION_DRIVER=database') !== false) {
    $env_content = str_replace('SESSION_DRIVER=database', 'SESSION_DRIVER=file', $env_content);
    echo "<p style='color:green;'>✅ Changed SESSION_DRIVER from database to file.</p>";
} else {
    echo "<p style='color:orange;'>⚠️ SESSION_DRIVER is not set to 'database'. No change made.</p>";
}

// Replace CACHE_STORE (just in case)
if (strpos($env_content, 'CACHE_STORE=database') !== false) {
    $env_content = str_replace('CACHE_STORE=database', 'CACHE_STORE=file', $env_content);
    echo "<p style='color:green;'>✅ Changed CACHE_STORE from database to file.</p>";
}

// Save the file
if (file_put_contents($env_path, $env_content)) {
    echo "<p style='color:blue; font-weight:bold;'>✅ Successfully saved .env file!</p>";
    echo "<p>Your app no longer requires a database to load the homepage. <b>Go back and refresh your homepage now!</b></p>";
} else {
    echo "<p style='color:red;'>❌ Failed to save .env file. Check permissions.</p>";
}
