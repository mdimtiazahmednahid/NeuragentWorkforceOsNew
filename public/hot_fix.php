<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Vite Development Server Issue...</h2>";

$hotFile = __DIR__ . '/hot';

if (file_exists($hotFile)) {
    if (unlink($hotFile)) {
        echo "<p style='color:green; font-weight:bold;'>✅ Successfully deleted the 'hot' file!</p>";
        echo "<p>Your site will now correctly load the compiled production CSS and JS!</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to delete the 'hot' file. Check permissions.</p>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ The 'hot' file does not exist. (It was already deleted).</p>";
}
