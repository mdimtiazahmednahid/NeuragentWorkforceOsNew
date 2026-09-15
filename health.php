<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>System Health Check</h2>";

// Check PHP Version
echo "PHP Version: " . phpversion() . "<br>";

// Check Directories
$dirs = [
    'storage',
    'storage/app',
    'storage/framework',
    'storage/framework/views',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        echo "<span style='color:red;'>❌ Directory Missing: $dir (I will try to create it...)</span><br>";
        @mkdir($path, 0755, true);
        if (is_dir($path)) {
            echo "<span style='color:green;'>✅ Created $dir successfully!</span><br>";
        } else {
            echo "<span style='color:red;'>❌ Failed to create $dir. Please create it manually in the File Manager!</span><br>";
        }
    } else {
         echo "<span style='color:green;'>✅ Directory Exists: $dir</span><br>";
    }
}

// Boot Laravel to see the real error
echo "<h2>Booting Laravel to find the real error...</h2>";
try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    echo "<span style='color:green;'>✅ Laravel booted successfully! No errors found.</span>";
} catch (\Throwable $e) {
    echo "<h3 style='color:red;'>🚨 THE REAL ERROR IS:</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (Line " . $e->getLine() . ")</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
