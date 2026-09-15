<?php

use Livewire\Volt\Component;
use App\Models\Task;
use App\Models\AttendanceSession;
use App\Models\BreakSession;
use Carbon\Carbon;

new class extends Component {
    public $myTasksCount = 0;
    public $dueTasksCount = 0;
    public $workedHours = 0;
    public $badgesCount = 0;

    public function mount()
    {
        $userId = auth()->id();
        
        $this->myTasksCount = Task::where('assignee_id', $userId)->count();
        
        $this->dueTasksCount = Task::where('assignee_id', $userId)
            ->whereNotIn('status', ['COMPLETED', 'DONE'])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(3))
            ->count();
            
        $totalSeconds = 0;
        foreach (AttendanceSession::where('user_id', $userId)->whereNotNull('check_out_time')->get() as $session) {
            $in = Carbon::parse($session->check_in_time);
            $out = Carbon::parse($session->check_out_time);
            
            $breakSeconds = 0;
            foreach (BreakSession::where('attendance_session_id', $session->id)->whereNotNull('break_end')->get() as $b) {
                $bIn = Carbon::parse($b->break_start);
                $bOut = Carbon::parse($b->break_end);
                $breakSeconds += max(0, $bOut->diffInSeconds($bIn));
            }
            
            $totalSeconds += max(0, $out->diffInSeconds($in) - $breakSeconds);
        }
        $this->workedHours = round($totalSeconds / 3600, 1);
        
        $this->badgesCount = auth()->user()->badges()->count();
    }
}; ?>

<div class="hidden lg:flex items-center gap-4 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 px-4 py-1.5 rounded-full border border-zinc-200 dark:border-zinc-700">
    <div class="flex items-center gap-1.5 cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors" title="My Badges">
        <flux:icon.trophy class="size-4 text-amber-500" /> 
        <span>{{ $badgesCount }}</span>
    </div>
    <div class="h-3 w-px bg-zinc-300 dark:bg-zinc-600"></div>
    <div class="flex items-center gap-1.5 cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors" title="Total Tasks">
        <flux:icon.clipboard-document-check class="size-4 text-blue-500" /> 
        <span>{{ $myTasksCount }}</span>
    </div>
    <div class="h-3 w-px bg-zinc-300 dark:bg-zinc-600"></div>
    <div class="flex items-center gap-1.5 cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors" title="Worked Hours">
        <flux:icon.clock class="size-4 text-green-500" /> 
        <span>{{ $workedHours }}h</span>
    </div>
    <div class="h-3 w-px bg-zinc-300 dark:bg-zinc-600"></div>
    <div class="flex items-center gap-1.5 cursor-pointer hover:text-zinc-900 dark:hover:text-white transition-colors" title="Tasks Due Soon">
        <flux:icon.exclamation-circle class="size-4 text-red-500" /> 
        <span>{{ $dueTasksCount }}</span>
    </div>
</div>
