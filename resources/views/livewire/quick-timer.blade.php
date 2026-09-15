<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\AttendanceSession;
use App\Models\BreakSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?AttendanceSession $activeSession = null;
    public ?BreakSession $activeBreak = null;
    
    public $status = 'OFFLINE'; // OFFLINE, WORKING, ON_BREAK
    public $totalMinutes = 0;
    
    public function mount()
    {
        $this->loadState();
    }
    
    #[On('attendance-updated')]
    public function loadState()
    {
        if (!Auth::check()) return;
        
        $sessions = AttendanceSession::with('breaks')
            ->where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->get();
            
        $this->activeSession = $sessions->last();
        
        // Calculate total minutes for the day
        $this->totalMinutes = 0;
        foreach ($sessions as $session) {
            $endTime = $session->check_out_time ?: Carbon::now();
            $sessionMins = $session->check_in_time->diffInMinutes($endTime);
            $breakMins = 0;
            foreach ($session->breaks as $break) {
                $bEnd = $break->break_end ?: Carbon::now();
                $breakMins += $break->break_start->diffInMinutes($bEnd);
            }
            $this->totalMinutes += ($sessionMins - $breakMins);
        }
        
        if ($this->activeSession && !$this->activeSession->check_out_time) {
            $this->activeBreak = BreakSession::where('attendance_session_id', $this->activeSession->id)
                ->whereNull('break_end')
                ->first();
                
            if ($this->activeBreak) {
                $this->status = 'ON_BREAK';
            } else {
                $this->status = 'WORKING';
            }
        } else {
            $this->activeSession = null;
            $this->activeBreak = null;
            $this->status = 'OFFLINE';
        }
    }
    
    public function checkIn()
    {
        if ($this->activeSession && !$this->activeSession->check_out_time) return;
        
        AttendanceSession::create([
            'user_id' => Auth::id(),
            'date' => Carbon::today(),
            'check_in_time' => Carbon::now(),
            'status' => 'PRESENT'
        ]);
        
        $this->dispatch('attendance-updated');
        $this->loadState();
    }
    
    public function checkOut()
    {
        if (!$this->activeSession || $this->activeSession->check_out_time) return;
        
        if ($this->activeBreak) {
            $this->activeBreak->update(['break_end' => Carbon::now()]);
        }
        
        $this->activeSession->update([
            'check_out_time' => Carbon::now()
        ]);
        
        $this->dispatch('attendance-updated');
        $this->loadState();
    }
    
    public function startBreak()
    {
        if (!$this->activeSession || $this->activeSession->check_out_time || $this->activeBreak) return;
        
        BreakSession::create([
            'attendance_session_id' => $this->activeSession->id,
            'break_start' => Carbon::now()
        ]);
        
        $this->dispatch('attendance-updated');
        $this->loadState();
    }
    
    public function endBreak()
    {
        if (!$this->activeBreak) return;
        
        $this->activeBreak->update([
            'break_end' => Carbon::now()
        ]);
        
        $this->dispatch('attendance-updated');
        $this->loadState();
    }
}; ?>

<div wire:poll.60s="loadState" class="relative">
    <flux:dropdown position="bottom" align="center">
        <!-- Trigger Button -->
        <flux:button size="sm" variant="{{ $status === 'WORKING' ? 'primary' : ($status === 'ON_BREAK' ? 'danger' : 'ghost') }}" icon="clock" class="font-mono tabular-nums">
            @if($status === 'WORKING')
                {{ str_pad((int) floor($totalMinutes / 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad((int) ($totalMinutes % 60), 2, '0', STR_PAD_LEFT) }}
            @elseif($status === 'ON_BREAK')
                On Break
            @else
                Off Clock
            @endif
        </flux:button>

        <!-- Dropdown Menu -->
        <flux:menu class="w-56">
            <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between mb-1">
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Time Clock</span>
                @if($status === 'WORKING')
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                @elseif($status === 'ON_BREAK')
                    <span class="size-2 rounded-full bg-amber-500 animate-pulse"></span>
                @else
                    <span class="size-2 rounded-full bg-zinc-300 dark:bg-zinc-700"></span>
                @endif
            </div>
            
            @if($status === 'OFFLINE')
                <flux:menu.item wire:click="checkIn" icon="arrow-right-end-on-rectangle" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">
                    <div class="flex flex-col">
                        <span class="font-medium">Check In</span>
                        <span class="text-xs text-zinc-400">Start a new shift</span>
                    </div>
                </flux:menu.item>
            @endif
            
            @if($status === 'WORKING')
                <flux:menu.item wire:click="startBreak" icon="pause" class="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300">
                    <div class="flex flex-col">
                        <span class="font-medium">Take Break</span>
                        <span class="text-xs text-zinc-400">Pause your active timer</span>
                    </div>
                </flux:menu.item>
                
                <flux:menu.item wire:click="checkOut" icon="arrow-left-start-on-rectangle" class="text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300">
                    <div class="flex flex-col">
                        <span class="font-medium">Check Out</span>
                        <span class="text-xs text-zinc-400">End your current shift</span>
                    </div>
                </flux:menu.item>
            @endif
            
            @if($status === 'ON_BREAK')
                <flux:menu.item wire:click="endBreak" icon="play" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">
                    <div class="flex flex-col">
                        <span class="font-medium">End Break</span>
                        <span class="text-xs text-zinc-400">Resume your active timer</span>
                    </div>
                </flux:menu.item>
            @endif
            
            <flux:menu.separator />
            
            <flux:menu.item href="{{ route('attendance') }}" wire:navigate icon="calendar-days">
                View Timesheet History
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
