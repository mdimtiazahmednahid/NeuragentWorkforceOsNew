<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Resetting Database...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    $output = new Symfony\Component\Console\Output\BufferedOutput;
    
    // Run migrate:fresh --seed
    $kernel->handle(
        new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'migrate:fresh',
            '--seed' => true,
            '--force' => true,
        ]),
        $output
    );
    
    echo "<pre>" . $output->fetch() . "</pre>";
    echo "<h3 style='color:green; font-weight:bold;'>✅ Database completely wiped, rebuilt, and seeded successfully!</h3>";
    echo "<p>You can now go to the login screen and log in with:</p>";
    echo "<b>Email:</b> superadmin@neuragent.local<br>";
    echo "<b>Password:</b> superadmin<br>";

} catch (\Exception $e) {
    echo "<h3 style='color:red;'>🚨 Error:</h3>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
}
