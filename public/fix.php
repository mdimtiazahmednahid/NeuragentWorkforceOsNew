<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Ultimate Laravel Cache Wrecker</h2>";

// Use dirname(__DIR__) to avoid "../" in paths which triggers open_basedir bugs on InfinityFree!
$base_path = dirname(__DIR__);

$directories_to_clear = [
    $base_path . '/bootstrap/cache/',
    $base_path . '/storage/framework/views/',
    $base_path . '/storage/framework/cache/data/',
];

foreach ($directories_to_clear as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                if (unlink($file)) {
                    $deleted++;
                } else {
                    echo "<p style='color:red;'>❌ Failed to delete: $file</p>";
                }
            }
        }
        echo "<p style='color:green;'>✅ Cleared $deleted files from " . $dir . "</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Directory not found: " . $dir . "</p>";
    }
}

// Ensure the log directory exists
$log_dir = $base_path . '/storage/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
    echo "<p style='color:green;'>✅ Created missing storage/logs directory.</p>";
}

echo "<h3>Now booting Laravel...</h3>";

try {
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    
    // OVERRIDE LARAVEL'S BROKEN ERROR HANDLER
    set_exception_handler(function($e) {
        echo "<h3 style='color:red;'>🚨 THE REAL FATAL ERROR IS:</h3>";
        echo "<b>Exception:</b> " . get_class($e) . "<br>";
        echo "<b>Message:</b> " . $e->getMessage() . "<br>";
        echo "<b>File:</b> " . $e->getFile() . ":" . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        exit;
    });

    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        echo "<h3 style='color:orange;'>⚠️ FATAL PHP WARNING:</h3>";
        echo "<b>Message:</b> " . $errstr . "<br>";
        echo "<b>File:</b> " . $errfile . ":" . $errline . "<br>";
        exit;
    });

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    
    echo "<span style='color:green;'>✅ Laravel booted successfully! The cache is clear! Go to your homepage!</span>";

} catch (\Throwable $e) {
    echo "<h3 style='color:red;'>🚨 CAUGHT EXCEPTION:</h3>";
    echo "<b>Message:</b> " . $e->getMessage() . "<br>";
    echo "<b>File:</b> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
