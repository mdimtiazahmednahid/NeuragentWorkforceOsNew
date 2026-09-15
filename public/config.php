<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Caching & Optimizing Application...</h2>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    $commands = [
        'config:cache',
        'route:cache',
        'view:cache',
        'event:cache',
    ];
    
    foreach ($commands as $command) {
        $output = new Symfony\Component\Console\Output\BufferedOutput;
        $kernel->handle(
            new Symfony\Component\Console\Input\ArrayInput([
                'command' => $command,
            ]),
            $output
        );
        echo "<h3>$command</h3>";
        echo "<pre>" . $output->fetch() . "</pre>";
    }
    
    echo "<p style='color:green; font-weight:bold;'>Configuration cached successfully!</p>";

} catch (\Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
