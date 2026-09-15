<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Pushing Project Restart Tasks...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Find Users
    $nahid = \App\Models\User::where('email', 'mdimtiazahmednahid@gmail.com')->first();
    $anusha = \App\Models\User::where('email', 'anusha.digitalradiobangla@gmail.com')->first();
    $superadmin = \App\Models\User::where('email', 'superadmin@neuragent.local')->first();
    
    $creatorId = $superadmin ? $superadmin->id : 1;

    if (!$nahid || !$anusha) {
        die("<p style='color:red;'>❌ Error: Could not find Nahid or Anusha in the database! Make sure they are restored first.</p>");
    }

    $nahidTasks = [
        "Finalize Project Restart technical direction",
        "Define EdTech solution architecture",
        "Build a polished LMS / Training Management Demo",
        "Build Admin Dashboard",
        "Build Student Dashboard",
        "Build course & batch management",
        "Build attendance & assignment modules",
        "Build quiz/exam & result modules",
        "Add certificate functionality",
        "Build AI Student Assistant demo",
        "Prepare automation demo",
        "Prepare technical portfolio/case studies",
        "Build Project Restart landing page",
        "Deploy LMS demo",
        "Deploy AI demo",
        "Ensure mobile responsiveness",
        "Perform QA, security & performance checks",
        "Prepare technical demo for client meetings",
        "Support Anusha with technical information for marketing materials",
        "Prepare technical answers for client questions"
    ];

    $anushaTasks = [
        "Define Ideal Customer Profile (ICP)",
        "Research EdTech/coaching/training markets",
        "Select priority countries",
        "Research competitors",
        "Identify client pain points",
        "Define service packages with Nahid",
        "Research international pricing",
        "Create company/service presentation",
        "Prepare marketing copy for landing page",
        "Build initial 100+ prospect database",
        "Find decision makers",
        "Collect business emails & LinkedIn profiles",
        "Analyze each prospect's business",
        "Identify potential problems/opportunities",
        "Prepare personalized outreach messages",
        "Prepare cold email sequences",
        "Prepare LinkedIn outreach",
        "Create follow-up strategy",
        "Manage CRM/prospect pipeline",
        "Track leads, replies, meetings & conversions",
        "Coordinate client meetings",
        "Handle initial client communication",
        "Prepare client proposals with Nahid",
        "Manage quotation/negotiation process"
    ];

    $jointTasks = [
        "Finalize Project Restart positioning",
        "Finalize service packages",
        "Review LMS/AI demos",
        "Select strongest portfolio projects",
        "Create case studies",
        "Prepare free Education Platform Audit",
        "Review and qualify prospects",
        "Attend client meetings together",
        "Prepare proposals",
        "Negotiate and close deals",
        "Review weekly progress"
    ];

    $count = 0;

    // Find or Create Project Restart
    $project = \App\Models\Project::firstOrCreate(
        ['name' => 'Project Restart'],
        [
            'description' => 'Phase One Execution',
            'status' => 'ACTIVE',
            'created_by' => $creatorId
        ]
    );

    // Prevent duplicates by deleting old tasks if you ran this before
    \Illuminate\Support\Facades\DB::table('tasks')->where('description', 'like', 'Project Restart%')->delete();

    // Insert Nahid's Tasks
    foreach ($nahidTasks as $index => $title) {
        $serial = $index + 1;
        \Illuminate\Support\Facades\DB::table('tasks')->insert([
            'title' => "{$serial}. {$title}",
            'description' => 'Project Restart — Phase One',
            'status' => 'TODO',
            'priority' => 'HIGH',
            'assignee_id' => $nahid->id,
            'project_id' => $project->id,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $count++;
    }

    // Insert Anusha's Tasks
    foreach ($anushaTasks as $index => $title) {
        $serial = $index + 1;
        \Illuminate\Support\Facades\DB::table('tasks')->insert([
            'title' => "{$serial}. {$title}",
            'description' => 'Project Restart — Phase One',
            'status' => 'TODO',
            'priority' => 'HIGH',
            'assignee_id' => $anusha->id,
            'project_id' => $project->id,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $count++;
    }

    // Insert Joint Tasks (Assigned to Both individually)
    foreach ($jointTasks as $index => $title) {
        $serial = $index + 1;
        // For Nahid
        \Illuminate\Support\Facades\DB::table('tasks')->insert([
            'title' => "[Joint Task] {$serial}. {$title}",
            'description' => 'Project Restart — Phase One (Joint Task with Anusha)',
            'status' => 'TODO',
            'priority' => 'URGENT',
            'assignee_id' => $nahid->id,
            'project_id' => $project->id,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $count++;

        // For Anusha
        \Illuminate\Support\Facades\DB::table('tasks')->insert([
            'title' => "[Joint Task] {$serial}. {$title}",
            'description' => 'Project Restart — Phase One (Joint Task with Nahid)',
            'status' => 'TODO',
            'priority' => 'URGENT',
            'assignee_id' => $anusha->id,
            'project_id' => $project->id,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $count++;
    }

    echo "<p style='color:green; font-weight:bold;'>✅ Successfully generated $count tasks and linked them to Project Restart!</p>";
    echo "<p>Tasks have been assigned directly to Nahid and Anusha.</p>";
    echo "<p>Joint tasks have been created twice (one for each) so you both have it on your dashboards!</p>";

} catch (\Exception $e) {
    echo "<h3 style='color:red;'>🚨 Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
