<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Adding New User: Md Ashraf Ali...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $email = 'ashraf.digitalradiobangla@gmail.com';
    $user = \App\Models\User::where('email', $email)->first();

    if ($user) {
        echo "<p style='color:orange;'>⚠️ User already exists: $email</p>";
    } else {
        $user = \App\Models\User::create([
            'name' => 'Md ashraf ali',
            'full_name' => 'Md Ashraf Ali',
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'role' => 'CONTRIBUTOR',
            'status' => 'ACTIVE'
        ]);
        echo "<p style='color:green; font-weight:bold;'>✅ User added successfully!</p>";
        echo "<p><b>Email:</b> ashraf.digitalradiobangla@gmail.com</p>";
        echo "<p><b>Temporary Password:</b> password123</p>";
        echo "<p>(He can log in and change his password later)</p>";
    }

} catch (\Exception $e) {
    echo "<h3 style='color:red;'>🚨 Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
