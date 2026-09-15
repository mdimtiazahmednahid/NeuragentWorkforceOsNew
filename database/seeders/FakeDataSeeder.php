<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\AttendanceSession;
use App\Models\ContributionPoint;
use App\Models\Badge;
use App\Models\Message;
use App\Models\WhatsAppNotification;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Roles
        $employeeRole = Role::firstOrCreate(
            ['name' => 'EMPLOYEE'],
            ['display_name' => 'Employee', 'hierarchy_level' => 10, 'is_system' => true]
        );

        // 2. Setup Departments
        $engineering = Department::firstOrCreate(
            ['code' => 'ENG'],
            ['name' => 'Engineering', 'organization' => 'Hexagon Kingdom']
        );
        $marketing = Department::firstOrCreate(
            ['code' => 'MKT'],
            ['name' => 'Marketing', 'organization' => 'Hexagon Kingdom']
        );

        // 3. Create Users
        $users = [];
        $userNames = ['Alice Smith', 'Bob Jones', 'Charlie Brown'];
        foreach ($userNames as $index => $name) {
            $firstName = explode(' ', $name)[0];
            $users[] = User::firstOrCreate(
                ['email' => strtolower($firstName) . '@example.com'],
                [
                    'name' => $firstName,
                    'full_name' => $name,
                    'username' => strtolower($firstName),
                    'password' => Hash::make('password123'),
                    'role' => 'EMPLOYEE',
                    'role_id' => $employeeRole->id,
                    'department_id' => $index % 2 == 0 ? $engineering->id : $marketing->id,
                    'status' => 'ACTIVE',
                ]
            );
        }
        $allUsers = User::all();

        // 4. Create Projects
        $projects = [];
        $projectNames = ['Website Redesign', 'Q3 Marketing Campaign', 'Mobile App V2'];
        foreach ($projectNames as $name) {
            $projects[] = Project::firstOrCreate(
                ['name' => $name],
                [
                    'description' => 'A comprehensive initiative for ' . $name,
                    'status' => 'ACTIVE',
                    'start_date' => Carbon::now()->subMonths(rand(1, 3))->toDateString(),
                    'end_date' => Carbon::now()->addMonths(rand(1, 4))->toDateString(),
                ]
            );
        }

        // 5. Create Tasks
        $taskTitles = ['Design mockups', 'Setup database', 'Write copy', 'Test deployment', 'Fix login bug'];
        foreach ($projects as $project) {
            foreach ($taskTitles as $title) {
                Task::firstOrCreate(
                    ['title' => $title . ' for ' . substr($project->name, 0, 5)],
                    [
                        'project_id' => $project->id,
                        'assignee_id' => $users[array_rand($users)]->id,
                        'created_by' => $allUsers->first()->id, // Super Admin
                        'status' => ['TODO', 'IN_PROGRESS', 'DONE'][rand(0, 2)],
                        'priority' => ['LOW', 'MEDIUM', 'HIGH'][rand(0, 2)],
                        'deadline' => Carbon::now()->addDays(rand(-5, 15))->toDateString(),
                    ]
                );
            }
        }

        // 6. Create Attendance Sessions (Past 3 Days)
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $date = Carbon::now()->subDays($i);
                // Randomize slightly
                $checkIn = $date->copy()->setTime(rand(8, 10), rand(0, 59), 0);
                $checkOut = $date->copy()->setTime(rand(16, 18), rand(0, 59), 0);
                
                AttendanceSession::firstOrCreate(
                    ['user_id' => $user->id, 'date' => $date->toDateString()],
                    [
                        'check_in_time' => $checkIn,
                        'check_out_time' => $checkOut,
                        'status' => 'PRESENT',
                    ]
                );
            }
        }

        // 7. Gamification / Points
        $badge = Badge::firstOrCreate(
            ['name' => 'Fast Starter'],
            ['icon' => 'rocket', 'description' => 'Completed a task early.']
        );

        foreach ($users as $user) {
            ContributionPoint::firstOrCreate(
                ['user_id' => $user->id, 'points' => 150],
                [
                    'reason' => 'Completed initial setup',
                ]
            );
            // Assign badge if not exists (many to many)
            if (!$user->badges()->where('badge_id', $badge->id)->exists()) {
                $user->badges()->attach($badge->id);
            }
        }

        // 8. Internal Chat Messages
        if (count($users) >= 2) {
            Message::firstOrCreate(
                ['message' => 'Hey, how is the project going?'],
                [
                    'sender_id' => $users[0]->id,
                    'receiver_id' => $users[1]->id,
                    'is_read' => true,
                    'created_at' => now()->subHours(2),
                ]
            );
            Message::firstOrCreate(
                ['message' => 'Going well! Almost done with the API.'],
                [
                    'sender_id' => $users[1]->id,
                    'receiver_id' => $users[0]->id,
                    'is_read' => false,
                    'created_at' => now()->subMinutes(30),
                ]
            );
        }

        // 9. WhatsApp Logs
        WhatsAppNotification::firstOrCreate(
            ['provider_message_id' => 'fake_msg_123'],
            [
                'recipient_phone' => '+1234567890',
                'message' => 'Your shift starts in 30 minutes.',
                'status' => 'delivered',
            ]
        );
        WhatsAppNotification::firstOrCreate(
            ['provider_message_id' => 'fake_msg_124'],
            [
                'recipient_phone' => '+0987654321',
                'message' => 'System maintenance scheduled.',
                'status' => 'failed',
            ]
        );

        // 10. In-App Notifications
        $admin = User::where('role', 'SUPER_ADMIN')->first();
        if ($admin) {
            Notification::firstOrCreate(
                ['title' => 'New User Registered'],
                [
                    'user_id' => $admin->id,
                    'message' => 'Alice Smith has joined the platform.',
                    'type' => 'INFO',
                    'is_read' => false,
                ]
            );
            Notification::firstOrCreate(
                ['title' => 'Project Overdue'],
                [
                    'user_id' => $admin->id,
                    'message' => 'Website Redesign is past its deadline.',
                    'type' => 'WARNING',
                    'is_read' => false,
                ]
            );
        }
    }
}

