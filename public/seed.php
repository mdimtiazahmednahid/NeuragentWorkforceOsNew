<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Seeding Super Admin User...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    $output = new Symfony\Component\Console\Output\BufferedOutput;
    
    $kernel->handle(
        new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'db:seed',
            '--force' => true,
        ]),
        $output
    );
    
    echo "<pre>" . $output->fetch() . "</pre>";
    echo "<p style='color:green; font-weight:bold;'>Database seeded successfully!</p>";

} catch (\Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
