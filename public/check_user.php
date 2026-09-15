<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Checking Database for Super Admin User...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $user = \App\Models\User::where('email', 'superadmin@neuragent.local')->first();
    
    if ($user) {
        echo "<p style='color:green;'>✅ User FOUND in database!</p>";
        echo "<pre>";
        print_r($user->toArray());
        echo "</pre>";
        
        // Let's test the password hash against 'superadmin'
        if (\Illuminate\Support\Facades\Hash::check('superadmin', $user->password)) {
            echo "<p style='color:green;'>✅ The password hash matches 'superadmin' perfectly!</p>";
        } else {
            echo "<p style='color:red;'>❌ The password hash does NOT match 'superadmin'!</p>";
        }
        
    } else {
        echo "<p style='color:red;'>❌ User NOT FOUND in database!</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
