<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Dashboard 500 Error...</h2>";

$targetFile = dirname(__DIR__) . '/resources/views/livewire/user-quick-stats.blade.php';

if (file_exists($targetFile)) {
    $content = file_get_contents($targetFile);
    
    // Replace the wrong column names
    $content = str_replace("whereNotNull('end_time')", "whereNotNull('break_end')", $content);
    $content = str_replace("Carbon::parse(\$b->start_time)", "Carbon::parse(\$b->break_start)", $content);
    $content = str_replace("Carbon::parse(\$b->end_time)", "Carbon::parse(\$b->break_end)", $content);
    
    file_put_contents($targetFile, $content);
    
    echo "<p style='color:green; font-weight:bold;'>✅ Successfully fixed the Quick Stats component!</p>";
    echo "<p>Please run <b>http://your-domain.com/public/fix.php</b> to clear the cache so the fix applies.</p>";
} else {
    echo "<p style='color:red;'>❌ Could not find the file: $targetFile</p>";
}
