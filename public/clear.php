<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Clearing All Caches...</h2>";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    
    $output = new Symfony\Component\Console\Output\BufferedOutput;
    
    $kernel->handle(
        new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'optimize:clear',
        ]),
        $output
    );
    
    echo "<pre>" . $output->fetch() . "</pre>";
    echo "<p style='color:green; font-weight:bold;'>All caches cleared successfully!</p>";

} catch (\Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
