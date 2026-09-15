<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Task;
use App\Models\Project;

new class extends Component {
    public $stats;
    
    public function mount()
    {
        $this->stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'total_tasks' => Task::count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Analytics Overview</h1>
            <p class="text-zinc-500 dark:text-zinc-400">High-level metrics and team performance insights.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Users Stat -->
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="size-12 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <flux:icon.users class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Users</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_users'] }} <span class="text-sm font-normal text-zinc-400">/ {{ $stats['total_users'] }}</span></p>
                    </div>
                </div>
            </div>
            
            <!-- Projects Stat -->
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="size-12 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <flux:icon.folder class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Projects</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['active_projects'] }} <span class="text-sm font-normal text-zinc-400">/ {{ $stats['total_projects'] }}</span></p>
                    </div>
                </div>
            </div>
            
            <!-- Tasks Stat -->
            <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="size-12 rounded-lg bg-green-50 dark:bg-green-500/10 flex items-center justify-center text-green-600 dark:text-green-400">
                        <flux:icon.check-circle class="size-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed Tasks</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['completed_tasks'] }} <span class="text-sm font-normal text-zinc-400">/ {{ $stats['total_tasks'] }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6 flex flex-col items-center justify-center min-h-[300px]">
                <flux:icon.chart-bar class="size-12 text-zinc-200 dark:text-zinc-700 mb-4" />
                <p class="text-zinc-500 dark:text-zinc-400 font-medium">Task Velocity Chart Placeholder</p>
                <p class="text-xs text-zinc-400 mt-1">Implement with preferred chart library</p>
            </div>
            
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6 flex flex-col items-center justify-center min-h-[300px]">
                <flux:icon.chart-pie class="size-12 text-zinc-200 dark:text-zinc-700 mb-4" />
                <p class="text-zinc-500 dark:text-zinc-400 font-medium">Project Status Distribution Placeholder</p>
                <p class="text-xs text-zinc-400 mt-1">Implement with preferred chart library</p>
            </div>
        </div>
    </div>
</div>
