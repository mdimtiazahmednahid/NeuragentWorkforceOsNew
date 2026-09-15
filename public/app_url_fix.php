<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing APP_URL for Production</h2>";

$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    
    // Replace APP_URL
    $content = preg_replace('/APP_URL=http:\/\/localhost:8000/m', 'APP_URL=https://neuragent.rf.gd', $content);
    $content = preg_replace('/APP_URL=http:\/\/localhost/m', 'APP_URL=https://neuragent.rf.gd', $content);
    
    // Also set environment to production just to be safe
    $content = preg_replace('/APP_ENV=local/m', 'APP_ENV=production', $content);
    
    file_put_contents($envFile, $content);
    
    echo "<p style='color:green; font-weight:bold;'>✅ Successfully updated APP_URL to https://neuragent.rf.gd!</p>";
} else {
    echo "<p style='color:red;'>❌ .env file not found.</p>";
}
