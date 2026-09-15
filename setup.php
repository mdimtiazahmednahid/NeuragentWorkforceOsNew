<?php
// InfinityFree Laravel Setup Script
// Upload this to htdocs and visit yourdomain.com/setup.php

$htaccess_content = <<<EOD
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^((?!public/).*)$ public/$1 [L,NC]
</IfModule>
EOD;

$index_content = <<<EOD
<?php
require_once __DIR__.'/public/index.php';
EOD;

echo "<h2>Fixing InfinityFree Deployment...</h2>";

// 1. Create .htaccess
if (file_put_contents(__DIR__ . '/.htaccess', $htaccess_content)) {
    echo "<p style='color:green;'>✅ Successfully created .htaccess</p>";
}

// 2. Create index.php
if (file_put_contents(__DIR__ . '/index.php', $index_content)) {
    echo "<p style='color:green;'>✅ Successfully created root index.php</p>";
}

// 3. Clear ALL caches including views!
$directories_to_clear = [
    __DIR__ . '/bootstrap/cache/',
    __DIR__ . '/storage/framework/views/',
    __DIR__ . '/storage/framework/cache/data/',
];

foreach ($directories_to_clear as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                unlink($file);
                $deleted++;
            }
        }
        echo "<p style='color:green;'>✅ Cleared $deleted files from " . basename(dirname($dir)) . "/" . basename($dir) . "</p>";
    }
}

echo "<h3>Setup complete!</h3>";
echo "<p>Please visit your homepage now. If it works, you can safely delete this setup.php file.</p>";
