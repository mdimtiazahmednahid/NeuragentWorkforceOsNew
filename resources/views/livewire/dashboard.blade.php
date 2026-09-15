<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\Task;
use App\Models\Project;
use App\Models\AttendanceSession;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $user;
    
    // Insights Data
    public $roleGreeting = '';
    public $roleSubtitle = '';
    public $productivityScore = 0;
    public $contributionPoints = 0;
    public $blockedCount = 0;
    public $overdueCount = 0;
    public $activeTask = null;
    public $reviewQueueCount = 0;
    
    // Chart Data
    public $statusDistribution = [];
    public $priorityMix = [];
    public $deliveryBalance = []; // active, completed, blocked, overdue
    
    public $recentLogs = [];
    
    // Today's hours
    public $todaySession = null;
    public $todayHours = 0;
    public $onBreak = false;
    
    // Overall Summary
    public $totalCompletedTasks = 0;
    public $totalBadges = 0;
    public $totalHours = 0;
    
    // Activity Graph
    public $activityGraph = [];

    #[On('attendance-updated')]
    public function loadData()
    {
        $this->user = Auth::user();
        $this->calculateInsights();
        $this->fetchLogs();
    }

    public function mount()
    {
        $this->user = Auth::user();
        $this->calculateInsights();
        $this->fetchLogs();
    }
    
    public function calculateInsights()
    {
        // Removed Role Greeting as requested
        
        // 2. Metrics
        $this->productivityScore = $this->user->productivity_score ?? 85;
        $this->contributionPoints = $this->user->contribution_points ?? 0;
        
        $this->blockedCount = Task::where('assignee_id', $this->user->id)->where('status', 'BLOCKED')->count();
        $this->overdueCount = Task::where('assignee_id', $this->user->id)
                                ->where('deadline', '<', Carbon::today())
                                ->whereNotIn('status', ['COMPLETED', 'ARCHIVED', 'CANCELLED'])
                                ->count();
                                
        $this->reviewQueueCount = Task::where('status', 'REVIEW')->count();
        $this->activeTask = Task::where('assignee_id', $this->user->id)
                                ->where('status', 'IN_PROGRESS')
                                ->orderBy('updated_at', 'desc')
                                ->first();
                                
        // 3. Status Distribution
        $statuses = Task::select('status', DB::raw('count(*) as count'))
            ->where('assignee_id', $this->user->id)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $this->statusDistribution = [
            'TODO' => $statuses['TODO'] ?? 0,
            'IN_PROGRESS' => $statuses['IN_PROGRESS'] ?? 0,
            'REVIEW' => $statuses['REVIEW'] ?? 0,
            'COMPLETED' => $statuses['COMPLETED'] ?? 0,
            'BLOCKED' => $statuses['BLOCKED'] ?? 0,
        ];
        
        // 4. Priority Mix
        $priorities = Task::select('priority', DB::raw('count(*) as count'))
            ->where('assignee_id', $this->user->id)
            ->whereNotIn('status', ['COMPLETED', 'ARCHIVED', 'CANCELLED'])
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
            
        $this->priorityMix = [
            'LOW' => $priorities['LOW'] ?? 0,
            'MEDIUM' => $priorities['MEDIUM'] ?? 0,
            'HIGH' => $priorities['HIGH'] ?? 0,
            'URGENT' => $priorities['URGENT'] ?? 0,
        ];
        
        // 5. Delivery Balance
        $activeWork = ($statuses['TODO'] ?? 0) + ($statuses['IN_PROGRESS'] ?? 0) + ($statuses['REVIEW'] ?? 0);
        $completedWork = $statuses['COMPLETED'] ?? 0;
        
        $totalWork = $activeWork + $completedWork + $this->blockedCount + $this->overdueCount;
        $totalWork = $totalWork > 0 ? $totalWork : 1; // prevent divide by zero
        
        $this->deliveryBalance = [
            'active' => round(($activeWork / $totalWork) * 100),
            'completed' => round(($completedWork / $totalWork) * 100),
            'blocked' => round(($this->blockedCount / $totalWork) * 100),
            'overdue' => round(($this->overdueCount / $totalWork) * 100),
        ];
        
        // 6. Attendance Hours
        $sessions = AttendanceSession::with('breaks')->where('user_id', $this->user->id)->whereDate('date', Carbon::today())->get();
        
        $this->todaySession = $sessions->last();
        $this->todayHours = 0;
        
        if ($sessions->count() > 0) {
            $totalMinutes = 0;
            foreach ($sessions as $session) {
                $endTime = $session->check_out_time ?: Carbon::now();
                $sessionMins = $session->check_in_time->diffInMinutes($endTime);
                $breakMins = 0;
                foreach ($session->breaks as $break) {
                    $bEnd = $break->break_end ?: Carbon::now();
                    $breakMins += $break->break_start->diffInMinutes($bEnd);
                }
                $totalMinutes += ($sessionMins - $breakMins);
            }
            $this->todayHours = round($totalMinutes / 60, 1);
            $this->onBreak = $this->todaySession && $this->todaySession->breaks()->whereNull('break_end')->exists();
        }
        
        // 7. Overall Summary
        $this->totalCompletedTasks = Task::where('assignee_id', $this->user->id)->where('status', 'COMPLETED')->count();
        $this->totalBadges = DB::table('badge_user')->where('user_id', $this->user->id)->count(); 
        
        $allSessions = AttendanceSession::with('breaks')->where('user_id', $this->user->id)->get();
        $totalAllMinutes = 0;
        foreach($allSessions as $session) {
            $endTime = $session->check_out_time ?: Carbon::now();
            $sessionMins = $session->check_in_time->diffInMinutes($endTime);
            $breakMins = 0;
            foreach ($session->breaks as $break) {
                $bEnd = $break->break_end ?: Carbon::now();
                $breakMins += $break->break_start->diffInMinutes($bEnd);
            }
            $totalAllMinutes += ($sessionMins - $breakMins);
        }
        $this->totalHours = round($totalAllMinutes / 60, 1);
        
        // 8. GitHub Activity Graph (last 42 days)
        $days = 42;
        $startDate = Carbon::today()->subDays($days - 1);
        
        $logs = ActivityLog::where('user_id', $this->user->id)
            ->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function($log) {
                return $log->created_at->format('Y-m-d');
            });
            
        $this->activityGraph = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $count = isset($logs[$date]) ? $logs[$date]->count() : 0;
            
            $intensity = 0;
            if ($count > 0) $intensity = 1;
            if ($count > 3) $intensity = 2;
            if ($count > 7) $intensity = 3;
            if ($count > 12) $intensity = 4;
            
            $this->activityGraph[] = [
                'date' => $date,
                'count' => $count,
                'intensity' => $intensity
            ];
        }
    }
    
    public function fetchLogs()
    {
        $this->recentLogs = ActivityLog::with('user')
            ->where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-7xl mx-auto pb-10">
        
        <!-- User Quick Actions & Summary -->
        <div class="bg-gradient-to-r from-indigo-600 to-violet-700 rounded-xl p-4 text-white shadow-lg flex flex-col sm:flex-row justify-between items-center gap-4 relative overflow-hidden">
            
            <!-- Activity Graph Background -->
            <div class="absolute right-0 top-1/2 -translate-y-1/2 opacity-20 pointer-events-none hidden md:flex items-center justify-end pr-4 h-full" style="mask-image: linear-gradient(to right, transparent, black 40%); -webkit-mask-image: linear-gradient(to right, transparent, black 40%);">
                <div class="grid grid-rows-7 gap-1" style="grid-auto-flow: column;">
                    @foreach($activityGraph as $day)
                        @php
                            $bgClass = 'bg-white/10';
                            if ($day['intensity'] == 1) $bgClass = 'bg-white/40';
                            if ($day['intensity'] == 2) $bgClass = 'bg-white/60';
                            if ($day['intensity'] == 3) $bgClass = 'bg-white/80';
                            if ($day['intensity'] == 4) $bgClass = 'bg-white';
                        @endphp
                        <div class="w-2.5 h-2.5 rounded-sm {{ $bgClass }}"></div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto relative z-10">
                @if($user->avatar)
                    <div class="size-10 rounded-full overflow-hidden border border-white/20 shrink-0 shadow-sm">
                        <img src="{{ Storage::url($user->avatar) }}" class="size-full object-cover" />
                    </div>
                @else
                    <flux:avatar size="md" :name="$user->name" />
                @endif
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold truncate">{{ $user->name }}</h1>
                    <p class="text-indigo-100/90 text-xs truncate mb-2">{{ str_replace('_', ' ', $user->role ?? 'Member') }}</p>
                    
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs text-indigo-100 font-medium">
                        <div class="flex items-center gap-1" title="Total Completed Tasks">
                            <flux:icon.check-circle class="size-3.5" />
                            {{ $totalCompletedTasks }} Tasks
                        </div>
                        <div class="flex items-center gap-1" title="Total Earned Badges">
                            <flux:icon.star class="size-3.5" />
                            {{ $totalBadges }} Badges
                        </div>
                        <div class="flex items-center gap-1" title="Total Tracked Hours">
                            <flux:icon.clock class="size-3.5" />
                            {{ $totalHours }}h Working
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-2 w-full sm:w-auto relative z-10">
                @if(!$todaySession)
                    <a href="{{ route('attendance') }}" wire:navigate class="flex-1 sm:flex-none text-center bg-white text-indigo-700 px-4 py-2 rounded-lg font-bold hover:bg-indigo-50 transition-colors shadow-sm inline-flex items-center justify-center gap-2 text-sm">
                        <flux:icon.check-circle class="size-4" /> Check In
                    </a>
                @elseif(!$todaySession->check_out_time)
                    @if($onBreak)
                        <a href="{{ route('attendance') }}" wire:navigate class="flex-1 sm:flex-none text-center bg-amber-400 text-amber-950 hover:bg-amber-300 px-4 py-2 rounded-lg font-bold transition-colors shadow-sm inline-flex items-center justify-center gap-2 text-sm">
                            <flux:icon.play class="size-4" /> End Break
                        </a>
                    @else
                        <a href="{{ route('attendance') }}" wire:navigate class="flex-1 sm:flex-none text-center bg-indigo-900/50 hover:bg-indigo-900/70 text-white px-4 py-2 rounded-lg font-bold transition-colors border border-indigo-500/30 shadow-sm inline-flex items-center justify-center gap-2 text-sm">
                            <flux:icon.pause class="size-4" /> Break
                        </a>
                    @endif
                    <div class="flex-1 sm:flex-none text-center px-4 py-2 rounded-lg font-bold text-white bg-black/20 border border-white/10 shadow-sm inline-flex items-center justify-center gap-2 text-sm">
                        <flux:icon.clock class="size-4 opacity-70" /> {{ $todayHours }}h
                    </div>
                @else
                    <span class="w-full sm:w-auto justify-center bg-emerald-500/20 text-emerald-100 border border-emerald-400/30 px-4 py-2 rounded-lg font-bold inline-flex items-center gap-2 text-sm">
                        <flux:icon.check-circle class="size-4" /> Shift Done
                    </span>
                @endif
            </div>
        </div>

        <!-- 3 Top Insight Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- 1. Productivity -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 mb-4 relative z-10">
                    <flux:icon.chart-bar class="size-5" />
                    <span class="text-sm font-medium uppercase tracking-wider">Productivity</span>
                </div>
                <div class="relative z-10">
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $productivityScore }}<span class="text-lg text-zinc-400">%</span></h3>
                    <p class="text-emerald-600 dark:text-emerald-400 font-medium text-sm mt-1">+{{ $contributionPoints }} CP Earned</p>
                </div>
                <div class="absolute -right-4 -bottom-4 opacity-5 text-emerald-500">
                    <flux:icon.chart-bar class="size-32" />
                </div>
            </div>

            <!-- 3. Attention -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between {{ ($blockedCount > 0 || $overdueCount > 0) ? 'border-l-4 border-l-red-500' : '' }}">
                <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 mb-4">
                    <flux:icon.exclamation-triangle class="size-5 {{ ($blockedCount > 0 || $overdueCount > 0) ? 'text-red-500' : '' }}" />
                    <span class="text-sm font-medium uppercase tracking-wider">Attention</span>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $blockedCount + $overdueCount }} <span class="text-lg font-normal text-zinc-500">issues</span></h3>
                    <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-1">
                        <span class="text-red-500 font-medium">{{ $blockedCount }} Blocked</span> &middot; 
                        <span class="text-amber-500 font-medium">{{ $overdueCount }} Overdue</span>
                    </p>
                </div>
            </div>

            <!-- 4. Next Action -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col justify-between">
                <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 mb-4">
                    <flux:icon.arrow-right-circle class="size-5 text-indigo-500" />
                    <span class="text-sm font-medium uppercase tracking-wider">Next Action</span>
                </div>
                <div>
                    @if($activeTask)
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white line-clamp-2 leading-tight" title="{{ $activeTask->title }}">{{ $activeTask->title }}</h3>
                        <p class="text-indigo-600 dark:text-indigo-400 font-medium text-xs mt-2 uppercase tracking-wide">In Progress</p>
                    @elseif($reviewQueueCount > 0)
                        <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $reviewQueueCount }}</h3>
                        <p class="text-amber-600 dark:text-amber-400 font-medium text-sm mt-1">Items to Review</p>
                    @else
                        <h3 class="text-lg font-bold text-zinc-400 dark:text-zinc-500 italic">Clear queue</h3>
                        <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-1">You're all caught up.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Delivery Balance Chart -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Delivery Balance</h3>
                    <p class="text-sm text-zinc-500">Distribution of your workload status</p>
                </div>
                
                <div class="flex-1 flex flex-col justify-center">
                    <!-- Custom CSS Horizontal Stacked Bar Chart for Balance -->
                    <div class="w-full h-8 flex rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-800 shadow-inner">
                        @if(array_sum($deliveryBalance) == 0)
                            <div class="w-full h-full bg-zinc-200 dark:bg-zinc-700"></div>
                        @else
                            @if($deliveryBalance['completed'] > 0)<div style="width: {{ $deliveryBalance['completed'] }}%" class="h-full bg-emerald-500 transition-all duration-1000"></div>@endif
                            @if($deliveryBalance['active'] > 0)<div style="width: {{ $deliveryBalance['active'] }}%" class="h-full bg-indigo-500 transition-all duration-1000"></div>@endif
                            @if($deliveryBalance['blocked'] > 0)<div style="width: {{ $deliveryBalance['blocked'] }}%" class="h-full bg-red-500 transition-all duration-1000"></div>@endif
                            @if($deliveryBalance['overdue'] > 0)<div style="width: {{ $deliveryBalance['overdue'] }}%" class="h-full bg-amber-500 transition-all duration-1000"></div>@endif
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8">
                        <div>
                            <div class="flex items-center gap-2 mb-1"><div class="size-3 rounded-full bg-emerald-500"></div><span class="text-xs text-zinc-500 uppercase font-semibold">Completed</span></div>
                            <span class="text-xl font-bold dark:text-white">{{ $deliveryBalance['completed'] }}%</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1"><div class="size-3 rounded-full bg-indigo-500"></div><span class="text-xs text-zinc-500 uppercase font-semibold">Active</span></div>
                            <span class="text-xl font-bold dark:text-white">{{ $deliveryBalance['active'] }}%</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1"><div class="size-3 rounded-full bg-red-500"></div><span class="text-xs text-zinc-500 uppercase font-semibold">Blocked</span></div>
                            <span class="text-xl font-bold dark:text-white">{{ $deliveryBalance['blocked'] }}%</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1"><div class="size-3 rounded-full bg-amber-500"></div><span class="text-xs text-zinc-500 uppercase font-semibold">Overdue</span></div>
                            <span class="text-xl font-bold dark:text-white">{{ $deliveryBalance['overdue'] }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Status Distribution -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Task Status</h3>
                    <p class="text-sm text-zinc-500">Current state of assigned tasks</p>
                </div>
                
                <div class="flex-1 flex flex-col justify-end gap-3">
                    @php
                        $maxStatus = max(array_merge($statusDistribution, [1])); // prevent zero division
                    @endphp
                    
                    @foreach(['TODO' => ['To Do', 'bg-zinc-300 dark:bg-zinc-600'], 'IN_PROGRESS' => ['In Progress', 'bg-indigo-500'], 'REVIEW' => ['In Review', 'bg-violet-500'], 'BLOCKED' => ['Blocked', 'bg-red-500'], 'COMPLETED' => ['Completed', 'bg-emerald-500']] as $key => $config)
                        <div class="flex items-center gap-3">
                            <div class="w-24 text-xs font-medium text-zinc-600 dark:text-zinc-400 text-right truncate">{{ $config[0] }}</div>
                            <div class="flex-1 h-5 bg-zinc-100 dark:bg-zinc-800/50 rounded-full overflow-hidden flex">
                                <div style="width: {{ ($statusDistribution[$key] / $maxStatus) * 100 }}%" class="h-full {{ $config[1] }} rounded-full transition-all duration-1000"></div>
                            </div>
                            <div class="w-8 text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ $statusDistribution[$key] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Priority Mix -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col lg:col-span-1">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Priority Mix</h3>
                    <p class="text-sm text-zinc-500">Active tasks by priority</p>
                </div>
                
                <div class="flex-1 flex items-end justify-between h-40 gap-2 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                    @php
                        $maxPriority = max(array_merge($priorityMix, [1]));
                    @endphp
                    
                    @foreach(['LOW' => 'bg-zinc-400', 'MEDIUM' => 'bg-blue-400', 'HIGH' => 'bg-orange-400', 'URGENT' => 'bg-red-500'] as $pKey => $pColor)
                        <div class="flex flex-col items-center flex-1 gap-2 group">
                            <span class="text-xs font-bold text-zinc-500 opacity-0 group-hover:opacity-100 transition-opacity">{{ $priorityMix[$pKey] }}</span>
                            <div class="w-full max-w-[40px] {{ $pColor }} rounded-t-sm transition-all duration-1000 hover:brightness-110" style="height: {{ max(5, ($priorityMix[$pKey] / $maxPriority) * 100) }}%;"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-2 px-1">
                    <span class="text-[10px] font-bold text-zinc-500 tracking-wider uppercase text-center w-full">Low</span>
                    <span class="text-[10px] font-bold text-zinc-500 tracking-wider uppercase text-center w-full">Med</span>
                    <span class="text-[10px] font-bold text-zinc-500 tracking-wider uppercase text-center w-full">High</span>
                    <span class="text-[10px] font-bold text-zinc-500 tracking-wider uppercase text-center w-full">Urg</span>
                </div>
            </div>

            <!-- Recent Activity Stream -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col lg:col-span-2 overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Recent Activity</h3>
                        <p class="text-sm text-zinc-500">Your latest actions in the system</p>
                    </div>
                    <flux:button href="{{ route('audit') }}" wire:navigate variant="ghost" size="sm">View All</flux:button>
                </div>
                
                <div class="flex-1">
                    @if(count($recentLogs) > 0)
                        <div class="space-y-4">
                            @foreach($recentLogs as $log)
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 shrink-0">
                                        @if($log->user && $log->user->avatar)
                                            <div class="size-8 rounded-full overflow-hidden border border-zinc-200 dark:border-zinc-800 shadow-sm">
                                                <img src="{{ Storage::url($log->user->avatar) }}" class="size-full object-cover" />
                                            </div>
                                        @else
                                            <flux:avatar size="sm" :name="$log->user->name ?? 'System'" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm text-zinc-800 dark:text-zinc-200">
                                            <span class="font-semibold">{{ $log->user->name ?? 'System' }}</span> 
                                            {{ $log->description }}
                                        </p>
                                        <p class="text-xs text-zinc-500 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full text-zinc-400 py-10">
                            <flux:icon.document-text class="size-12 opacity-20 mb-3" />
                            <p>No recent activity found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
