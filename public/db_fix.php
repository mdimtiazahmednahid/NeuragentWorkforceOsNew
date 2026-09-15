<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Database Configuration</h2>";

$env_path = dirname(__DIR__) . '/.env';

if (!file_exists($env_path)) {
    echo "<p style='color:red;'>❌ .env file not found at $env_path</p>";
    exit;
}

$env_content = file_get_contents($env_path);
$original_content = $env_content;

// Uncomment DB lines
$env_content = str_replace('# DB_HOST', 'DB_HOST', $env_content);
$env_content = str_replace('# DB_PORT', 'DB_PORT', $env_content);
$env_content = str_replace('# DB_DATABASE', 'DB_DATABASE', $env_content);
$env_content = str_replace('# DB_USERNAME', 'DB_USERNAME', $env_content);
$env_content = str_replace('# DB_PASSWORD', 'DB_PASSWORD', $env_content);
$env_content = str_replace('# DB_CONNECTION', 'DB_CONNECTION', $env_content);

if ($env_content !== $original_content) {
    if (file_put_contents($env_path, $env_content)) {
        echo "<p style='color:green; font-weight:bold;'>✅ Successfully uncommented the database credentials in your .env file!</p>";
        
        // Also clear config cache just in case Laravel dynamically cached it
        $config_cache = dirname(__DIR__) . '/bootstrap/cache/config.php';
        if (file_exists($config_cache)) {
            unlink($config_cache);
        }
        
        echo "<h3>Next Steps:</h3>";
        echo "<p>1. Go to <a href='/public/migration.php'>/public/migration.php</a> to create your database tables.</p>";
        echo "<p>2. Then try logging in again!</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to save .env file. Check permissions.</p>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ The database lines are already uncommented in your .env file!</p>";
}
