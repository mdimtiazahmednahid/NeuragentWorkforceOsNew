<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Storage Link (Profile Pictures)</h2>";

$targetFolder = dirname(__DIR__) . '/storage/app/public';
$linkFolder = dirname(__DIR__) . '/public/storage';

if (file_exists($linkFolder)) {
    echo "<p style='color:orange;'>⚠️ The storage link already exists!</p>";
} else {
    try {
        if (symlink($targetFolder, $linkFolder)) {
            echo "<p style='color:green; font-weight:bold;'>✅ Storage link created successfully!</p>";
            echo "<p>Your profile pictures and other uploaded files should now display correctly.</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to create symlink. Your hosting provider might have symlinks disabled.</p>";
            
            // Provide an alternative solution for shared hosting
            echo "<p><b>Alternative Solution for Shared Hosting:</b></p>";
            echo "<p>If symlinks are disabled, you need to change your filesystem disk to public.</p>";
            echo "<p>Open your <code>.env</code> file and change <code>FILESYSTEM_DISK=local</code> to <code>FILESYSTEM_DISK=public_html</code> (or similar depending on your host).</p>";
        }
    } catch (\Exception $e) {
        echo "<p style='color:red;'>🚨 Error: " . $e->getMessage() . "</p>";
    }
}
