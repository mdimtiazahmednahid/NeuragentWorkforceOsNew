<?php
$sqlitePath = __DIR__ . '/database/database.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);

$tablesToExport = ['roles', 'departments', 'users', 'projects', 'project_members', 'tasks'];
$exportData = [];

foreach ($tablesToExport as $table) {
    $stmt = $pdo->query("SELECT * FROM $table");
    if ($stmt) {
        $exportData[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$script = "<?php\n";
$script .= "ini_set('display_errors', 1);\n";
$script .= "ini_set('display_startup_errors', 1);\n";
$script .= "error_reporting(E_ALL);\n\n";
$script .= "echo '<h2>Restoring All Database Content...</h2>';\n\n";
$script .= "try {\n";
$script .= "    \$base_path = dirname(__DIR__);\n";
$script .= "    require \$base_path . '/vendor/autoload.php';\n";
$script .= "    \$app = require_once \$base_path . '/bootstrap/app.php';\n";
$script .= "    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);\n";
$script .= "    \$kernel->bootstrap();\n\n";
$script .= "    \\Illuminate\\Support\\Facades\\Schema::disableForeignKeyConstraints();\n\n";

foreach ($exportData as $table => $rows) {
    if (empty($rows)) continue;
    
    $script .= "    // Restoring $table\n";
    $script .= "    \$rows = " . var_export($rows, true) . ";\n";
    $script .= "    foreach (\$rows as \$row) {\n";
    if ($table === 'users') {
        $script .= "        \\Illuminate\\Support\\Facades\\DB::table('$table')->updateOrInsert(['email' => \$row['email']], \$row);\n";
    } elseif ($table === 'roles') {
        $script .= "        \\Illuminate\\Support\\Facades\\DB::table('$table')->updateOrInsert(['name' => \$row['name']], \$row);\n";
    } elseif (isset($rows[0]['id'])) {
        $script .= "        \\Illuminate\\Support\\Facades\\DB::table('$table')->updateOrInsert(['id' => \$row['id']], \$row);\n";
    } else {
        $script .= "        \\Illuminate\\Support\\Facades\\DB::table('$table')->insert(\$row);\n";
    }
    $script .= "    }\n";
    $script .= "    echo '<p style=\"color:green;\">✅ Restored table: $table (' . count(\$rows) . ' rows)</p>';\n\n";
}

$script .= "    \\Illuminate\\Support\\Facades\\Schema::enableForeignKeyConstraints();\n";
$script .= "    echo '<h3 style=\"color:blue;\">🎉 All data restored successfully!</h3>';\n";
$script .= "} catch (\\Exception \$e) {\n";
$script .= "    echo '<h3 style=\"color:red;\">🚨 Error:</h3>';\n";
$script .= "    echo '<p>' . \$e->getMessage() . '</p>';\n";
$script .= "}\n";

file_put_contents(__DIR__ . '/public/restore_all.php', $script);
echo "Full restore script generated at public/restore_all.php\n";
