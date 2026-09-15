<?php
$sqlitePath = __DIR__ . '/database/database.sqlite';
$pdo = new PDO('sqlite:' . $sqlitePath);
$stmt = $pdo->query("SELECT * FROM users WHERE id > 1");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$script = "<?php\n";
$script .= "ini_set('display_errors', 1);\n";
$script .= "ini_set('display_startup_errors', 1);\n";
$script .= "error_reporting(E_ALL);\n\n";
$script .= "echo '<h2>Restoring Previous Users...</h2>';\n\n";
$script .= "try {\n";
$script .= "    \$base_path = dirname(__DIR__);\n";
$script .= "    require \$base_path . '/vendor/autoload.php';\n";
$script .= "    \$app = require_once \$base_path . '/bootstrap/app.php';\n";
$script .= "    \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);\n";
$script .= "    \$kernel->bootstrap();\n\n";
$script .= "    \$users = " . var_export($users, true) . ";\n\n";
$script .= "    foreach (\$users as \$u) {\n";
$script .= "        // Clean up the array to remove nulls or specific IDs if needed, though ID preservation is good.\n";
$script .= "        \$exists = \\App\\Models\\User::where('email', \$u['email'])->first();\n";
$script .= "        if (!\$exists) {\n";
$script .= "            \\App\\Models\\User::insert(\$u);\n";
$script .= "            echo '<p style=\"color:green;\">✅ Restored user: ' . \$u['name'] . '</p>';\n";
$script .= "        } else {\n";
$script .= "            echo '<p style=\"color:orange;\">⚠️ User already exists: ' . \$u['name'] . '</p>';\n";
$script .= "        }\n";
$script .= "    }\n";
$script .= "    echo '<h3 style=\"color:blue;\">🎉 All previous users have been restored successfully!</h3>';\n";
$script .= "} catch (\\Exception \$e) {\n";
$script .= "    echo '<h3 style=\"color:red;\">🚨 Error:</h3>';\n";
$script .= "    echo '<p>' . \$e->getMessage() . '</p>';\n";
$script .= "}\n";

file_put_contents(__DIR__ . '/public/restore_users.php', $script);
echo "Restore script generated at public/restore_users.php\n";
