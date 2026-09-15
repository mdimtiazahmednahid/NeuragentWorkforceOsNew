<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2 style='color:purple;'>ULTIMATE DATABASE WIPE & REBUILD (V2)</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // 1. Manually Drop All Tables (BULLETPROOF)
    echo "<h3>Dropping all existing tables...</h3>";
    \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    
    $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
    
    if (empty($tables)) {
        echo "<p>No tables found in database.</p>";
    } else {
        foreach ($tables as $table) {
            $tableArray = (array)$table;
            $tableName = reset($tableArray);
            \Illuminate\Support\Facades\Schema::dropIfExists($tableName);
            echo "Dropped table: $tableName<br>";
        }
    }
    
    \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
    echo "<p style='color:green; font-weight:bold;'>All tables dropped successfully!</p>";

    // 2. Run Migrations
    echo "<h3>Running Migrations...</h3>";
    $output = new Symfony\Component\Console\Output\BufferedOutput;
    $kernel->handle(
        new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'migrate',
            '--force' => true,
        ]),
        $output
    );
    echo "<pre>" . $output->fetch() . "</pre>";

    // 3. Run Seeder
    echo "<h3>Running Seeder...</h3>";
    $output2 = new Symfony\Component\Console\Output\BufferedOutput;
    $kernel->handle(
        new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'db:seed',
            '--force' => true,
        ]),
        $output2
    );
    echo "<pre>" . $output2->fetch() . "</pre>";

    echo "<h3 style='color:green; font-weight:bold;'>✅ DATABASE COMPLETELY REBUILT!</h3>";
    echo "<p>You can now log in with:</p>";
    echo "<b>Email:</b> superadmin@neuragent.local<br>";
    echo "<b>Password:</b> superadmin<br>";

} catch (\Exception $e) {
    echo "<h3 style='color:red;'>🚨 Error:</h3>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
}
