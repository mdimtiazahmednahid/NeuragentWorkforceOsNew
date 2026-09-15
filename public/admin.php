<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Fixing Super Admin Account...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap(); // Boot the application so we can use Eloquent
    
    // 1. Ensure the Role exists
    $adminRole = \App\Models\Role::where('name', 'SUPER_ADMIN')->first();
    if (!$adminRole) {
        $adminRole = \App\Models\Role::create([
            'name' => 'SUPER_ADMIN',
            'display_name' => 'Super Admin',
            'hierarchy_level' => 0,
            'is_system' => true
        ]);
        echo "<p>Created SUPER_ADMIN role.</p>";
    } else {
        echo "<p>SUPER_ADMIN role already exists (ID: {$adminRole->id}).</p>";
    }

    // 2. Ensure Department exists
    $hqDept = \App\Models\Department::where('code', 'HQ')->first();
    if (!$hqDept) {
        $hqDept = \App\Models\Department::create([
            'code' => 'HQ',
            'name' => 'Headquarters',
            'organization' => 'Hexagon Kingdom'
        ]);
        echo "<p>Created HQ Department.</p>";
    } else {
        echo "<p>HQ Department already exists (ID: {$hqDept->id}).</p>";
    }

    // 3. Create or Update the Super Admin User
    $user = \App\Models\User::where('email', 'superadmin@neuragent.local')->first();
    
    $password = \Illuminate\Support\Facades\Hash::make('superadmin');
    
    if ($user) {
        $user->update([
            'password' => $password,
            'role_id' => $adminRole->id,
            'department_id' => $hqDept->id,
            'status' => 'ACTIVE'
        ]);
        echo "<p style='color:blue;'>User found! Forcefully updated password to <b>superadmin</b>.</p>";
    } else {
        $user = \App\Models\User::create([
            'name' => 'Superhero',
            'full_name' => 'System Super Admin',
            'username' => 'superhero',
            'email' => 'superadmin@neuragent.local',
            'password' => $password,
            'role' => 'SUPER_ADMIN',
            'role_id' => $adminRole->id,
            'department_id' => $hqDept->id,
            'status' => 'ACTIVE'
        ]);
        echo "<p style='color:blue;'>User created from scratch with password <b>superadmin</b>.</p>";
    }
    
    echo "<h3 style='color:green;'>✅ Success! You can now log in with:</h3>";
    echo "<b>Email:</b> superadmin@neuragent.local<br>";
    echo "<b>Password:</b> superadmin<br>";

} catch (\Exception $e) {
    echo "<h3 style='color:red;'>🚨 Error:</h3>";
    echo "<p>Message: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . ":" . $e->getLine() . "</p>";
}
