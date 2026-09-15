<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\AttendanceSession;
use App\Models\BreakSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $sessions = [];
    public ?AttendanceSession $activeSession = null;
    public ?BreakSession $activeBreak = null;
    public string $currentDate = '';

    public function mount()
    {
        $this->currentDate = Carbon::now()->format('l, F j, Y');
        $this->loadSession();
    }

    #[On('attendance-updated')]
    public function loadSession()
    {
        $this->sessions = AttendanceSession::with('breaks')
            ->where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->get();
            
        $latest = $this->sessions->last();
        if ($latest && !$latest->check_out_time) {
            $this->activeSession = $latest;
            $this->activeBreak = BreakSession::where('attendance_session_id', $this->activeSession->id)
                ->whereNull('break_end')
                ->first();
        } else {
            $this->activeSession = null;
            $this->activeBreak = null;
        }
    }

    public function checkIn()
    {
        if ($this->activeSession) return;
        
        AttendanceSession::create([
            'user_id' => Auth::id(),
            'date' => Carbon::today(),
            'check_in_time' => Carbon::now(),
            'status' => 'PRESENT'
        ]);
        
        $this->loadSession();
        
        $this->dispatch('attendance-updated');
    }

    public function checkOut()
    {
        if (!$this->activeSession || $this->activeSession->check_out_time) return;
        
        if ($this->activeBreak) {
            $this->endBreak();
        }
        
        $this->activeSession->update([
            'check_out_time' => Carbon::now()
        ]);
        
        $this->loadSession();
        
        $this->dispatch('attendance-updated');
    }

    public function startBreak()
    {
        if (!$this->activeSession || $this->activeSession->check_out_time || $this->activeBreak) return;
        
        BreakSession::create([
            'attendance_session_id' => $this->activeSession->id,
            'break_start' => Carbon::now()
        ]);
        
        $this->loadSession();
    }

    public function endBreak()
    {
        if (!$this->activeBreak) return;
        
        $this->activeBreak->update([
            'break_end' => Carbon::now()
        ]);
        
        $this->activeBreak = null;
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-5xl mx-auto">
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-8 text-center relative overflow-hidden">
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">{{ $currentDate }}</h2>
            <p class="text-zinc-500 dark:text-zinc-400 mb-8">Manage your daily attendance and breaks.</p>

            <div class="flex flex-wrap justify-center gap-4">
                @if(!$activeSession)
                    <!-- Not Checked In OR Currently Checked Out -->
                    <button wire:click="checkIn" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-md transition-colors text-lg inline-flex items-center gap-2">
                        <flux:icon.check-circle variant="outline" class="size-6" />
                        {{ $sessions->count() > 0 ? 'Check In Again' : 'Check In' }}
                    </button>
                    @if($sessions->count() > 0)
                        <div class="w-full mt-2 text-center text-sm text-zinc-500">You are currently checked out.</div>
                    @endif
                @else
                    <!-- Checked In -->
                    <div class="w-full flex flex-col sm:flex-row justify-center gap-4">
                        @if($activeBreak)
                            <button wire:click="endBreak" class="px-8 py-4 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-semibold shadow-md transition-colors text-lg inline-flex items-center justify-center gap-2">
                                <flux:icon.play variant="outline" class="size-6" />
                                End Break
                            </button>
                        @else
                            <button wire:click="startBreak" class="px-8 py-4 bg-amber-50 dark:bg-amber-500/10 hover:bg-amber-100 dark:hover:bg-amber-500/20 text-amber-600 dark:text-amber-500 border border-amber-200 dark:border-amber-500/30 rounded-xl font-semibold transition-colors text-lg inline-flex items-center justify-center gap-2">
                                <flux:icon.pause variant="outline" class="size-6" />
                                Start Break
                            </button>
                            <button wire:click="checkOut" class="px-8 py-4 bg-zinc-900 dark:bg-white hover:bg-zinc-800 dark:hover:bg-zinc-100 text-white dark:text-zinc-900 rounded-xl font-semibold shadow-md transition-colors text-lg inline-flex items-center justify-center gap-2">
                                <flux:icon.arrow-right-start-on-rectangle variant="outline" class="size-6" />
                                Check Out
                            </button>
                        @endif
                    </div>
                @endif
            </div>
            
            @if($sessions->count() > 0)
                <div class="mt-12 text-left">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Today's Timeline</h3>
                    <div class="relative border-l-2 border-zinc-200 dark:border-zinc-700 ml-3 space-y-6">
                        @foreach($sessions as $sess)
                            <div class="relative pl-6">
                                <span class="absolute -left-1.5 top-1.5 size-3 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-zinc-900"></span>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $sess->check_in_time->format('h:i A') }}</div>
                                <div class="font-medium text-zinc-900 dark:text-white">Checked In</div>
                            </div>
                            
                            @foreach($sess->breaks as $break)
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1.5 size-3 rounded-full bg-amber-500 ring-4 ring-white dark:ring-zinc-900"></span>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $break->break_start->format('h:i A') }} 
                                        @if($break->break_end)
                                            - {{ $break->break_end->format('h:i A') }}
                                        @else
                                            - (Ongoing)
                                        @endif
                                    </div>
                                    <div class="font-medium text-zinc-900 dark:text-white">Break</div>
                                </div>
                            @endforeach
                            
                            @if($sess->check_out_time)
                                <div class="relative pl-6">
                                    <span class="absolute -left-1.5 top-1.5 size-3 rounded-full bg-zinc-400 ring-4 ring-white dark:ring-zinc-900"></span>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $sess->check_out_time->format('h:i A') }}</div>
                                    <div class="font-medium text-zinc-900 dark:text-white">Checked Out</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>