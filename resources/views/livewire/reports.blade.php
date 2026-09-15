<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\AttendanceSession;
use App\Models\Task;
use Carbon\Carbon;

new class extends Component {
    public $reportDate;
    public $reportType = 'daily';
    
    public $attendanceStats = [];
    public $taskStats = [];
    
    public function mount()
    {
        $this->reportDate = Carbon::today()->format('Y-m-d');
        $this->generateReport();
    }
    
    public function generateReport()
    {
        $date = Carbon::parse($this->reportDate);
        
        // Attendance
        $this->attendanceStats = AttendanceSession::with(['user', 'breaks'])
            ->whereDate('date', $date)
            ->get()
            ->map(function($session) {
                return [
                    'name' => $session->user->name,
                    'check_in' => $session->check_in_time ? $session->check_in_time->format('h:i A') : '--:--',
                    'check_out' => $session->check_out_time ? $session->check_out_time->format('h:i A') : '--:--',
                    'total_hours' => $session->total_hours,
                ];
            });
            
        // Tasks completed that day
        $this->taskStats = Task::with('assignee')
            ->where('status', 'COMPLETED')
            ->whereDate('updated_at', $date)
            ->get();
    }
}; ?>

<div>
    <div class="flex flex-col gap-8 w-full max-w-6xl mx-auto">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Reports</h1>
                <p class="text-zinc-500 dark:text-zinc-400">View daily attendance and productivity summaries.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <form wire:submit="generateReport" class="flex items-center gap-2">
                    <flux:input type="date" wire:model="reportDate" />
                    <flux:button type="submit" variant="primary">Generate</flux:button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Attendance Report -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Attendance Summary</h2>
                    <p class="text-sm text-zinc-500">{{ \Carbon\Carbon::parse($reportDate)->format('F j, Y') }}</p>
                </div>
                
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Employee</th>
                                <th class="px-6 py-3 font-medium">Check In</th>
                                <th class="px-6 py-3 font-medium">Check Out</th>
                                <th class="px-6 py-3 font-medium">Hours</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($attendanceStats as $stat)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $stat['name'] }}</td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $stat['check_in'] ?? '--:--' }}</td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $stat['check_out'] ?? '--:--' }}</td>
                                    <td class="px-6 py-4 font-medium text-indigo-600 dark:text-indigo-400">{{ number_format((float)$stat['total_hours'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-zinc-500">No attendance records found for this date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Task Report -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Completed Tasks</h2>
                        <p class="text-sm text-zinc-500">{{ \Carbon\Carbon::parse($reportDate)->format('F j, Y') }}</p>
                    </div>
                    <span class="bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400 px-3 py-1 rounded-full text-sm font-bold">{{ count($taskStats) }} Total</span>
                </div>
                
                <div class="flex-1 overflow-y-auto max-h-[500px]">
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($taskStats as $task)
                            <div class="p-4 flex gap-4 items-start">
                                <div class="mt-0.5">
                                    <flux:icon.check-circle class="size-5 text-green-500" />
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white leading-tight">{{ $task->title }}</h4>
                                    <p class="text-xs text-zinc-500 mt-1">Completed by <strong>{{ $task->assignee?->name ?? 'Unknown' }}</strong></p>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-zinc-500">No tasks were marked as completed on this date.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
