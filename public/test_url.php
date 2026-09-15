<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$base_path = dirname(__DIR__);
require $base_path . '/vendor/autoload.php';
$app = require_once $base_path . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->bootstrap();

echo "APP_URL from env: " . env('APP_URL') . "<br>";
echo "Generated Asset URL: " . asset('build/assets/app.css') . "<br>";
echo "Generated Vite URL: " . app(\Illuminate\Foundation\Vite::class)->asset('resources/css/app.css') . "<br>";
