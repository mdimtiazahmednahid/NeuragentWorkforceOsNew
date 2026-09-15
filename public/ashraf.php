<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Pushing Tasks for Ashraf...</h2>";

try {
    $base_path = dirname(__DIR__);
    require $base_path . '/vendor/autoload.php';
    $app = require_once $base_path . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Find Ashraf
    $ashraf = \App\Models\User::where('email', 'ashraf.digitalradiobangla@gmail.com')->first();
    $superadmin = \App\Models\User::where('email', 'superadmin@neuragent.local')->first();
    
    $creatorId = $superadmin ? $superadmin->id : 1;

    if (!$ashraf) {
        die("<p style='color:red;'>❌ Error: Could not find Ashraf in the database! Please make sure his account (ashraf.digitalradiobangla@gmail.com) exists.</p>");
    }

    // Find or Create Digital Radio Bangla Project
    $project = \App\Models\Project::firstOrCreate(
        ['name' => 'Digital Radio Bangla'],
        [
            'description' => 'Digital Radio Bangla Content & Social Media',
            'status' => 'ACTIVE',
            'created_by' => $creatorId
        ]
    );

    // Prevent duplicates if run multiple times
    \Illuminate\Support\Facades\DB::table('tasks')->where('assignee_id', $ashraf->id)->where('project_id', $project->id)->delete();

    $ashrafTasks = [
        "Setup editing software & organize workspace folders",
        "Review top 5 performing shorts/reels in our niche",
        "Edit your first short video (Focus purely on pacing & clean cuts)",
        "Learn and apply dynamic, engaging auto-captions to a short",
        "Practice basic color grading on a raw video clip",
        "Add sound effects (SFX) and background music to a short",
        "Create a 15-second hyper-focused 'hook' short",
        "Edit a long-form podcast/video highlight into a vertical short",
        "Learn keyframing for smooth zoom & pan effects",
        "Find and overlay engaging B-roll footage over speaking parts",
        "Design an eye-catching custom thumbnail for a short",
        "Finalize, export, and review a fully polished daily short"
    ];

    $count = 0;

    // Insert Ashraf's Tasks
    foreach ($ashrafTasks as $index => $title) {
        $serial = $index + 1;
        $dayOffset = $index; // Task 1 is today, Task 2 is tomorrow, etc.
        
        $startDate = now()->addDays($dayOffset)->format('M d, Y');
        $deadline = now()->addDays($dayOffset + 1);
        $durationHours = 3; // Estimated hours per day

        \Illuminate\Support\Facades\DB::table('tasks')->insert([
            'title' => "Day {$serial}: {$title}",
            'description' => "Daily Skill Building Task for Digital Radio Bangla.\n\n**Schedule:**\n📅 Start Date: {$startDate}\n⏳ Estimated Duration: {$durationHours} Hours\n\n**Objective:** Complete this task by the end of your shift today.",
            'status' => 'TODO',
            'priority' => 'MEDIUM',
            'assignee_id' => $ashraf->id,
            'project_id' => $project->id,
            'deadline' => $deadline,
            'estimated_hours' => $durationHours,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $count++;
    }

    echo "<p style='color:green; font-weight:bold;'>✅ Successfully generated $count skill-building tasks for Ashraf!</p>";
    echo "<p>They have been linked to the 'Digital Radio Bangla' project and neatly numbered.</p>";

} catch (\Exception $e) {
    echo "<h3 style='color:red;'>🚨 Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
