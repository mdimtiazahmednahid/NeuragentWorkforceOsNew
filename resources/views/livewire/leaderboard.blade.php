<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Badge;
use App\Models\ContributionPoint;

new class extends Component {
    public $users;
    public $badges;
    
    // Assign badge form
    public $selectedUserId = '';
    public $selectedBadgeId = '';
    
    // Add points form
    public $pointsUserId = '';
    public $pointsAmount = 0;
    public $pointsReason = '';
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function canManageLeaderboard()
    {
        return auth()->user()->isLead || auth()->user()->isManagement;
    }
    
    public function loadData()
    {
        $this->users = User::withSum('contributionPoints as total_points', 'points')
            ->orderByDesc('total_points')
            ->get();
            
        $this->badges = Badge::all();
    }
    
    public function assignBadge()
    {
        if (!$this->canManageLeaderboard()) return;
        
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'selectedBadgeId' => 'required|exists:badges,id',
        ]);
        
        $user = User::find($this->selectedUserId);
        // Assuming many-to-many relationship defined in User model (which we created in Phase 1)
        if (!$user->badges()->where('badge_id', $this->selectedBadgeId)->exists()) {
            $user->badges()->attach($this->selectedBadgeId);
        }
        
        $this->reset(['selectedUserId', 'selectedBadgeId']);
        $this->loadData();
    }
    
    public function addPoints()
    {
        if (!$this->canManageLeaderboard()) return;
        
        $this->validate([
            'pointsUserId' => 'required|exists:users,id',
            'pointsAmount' => 'required|integer|not_in:0',
            'pointsReason' => 'required|string|max:255',
        ]);
        
        ContributionPoint::create([
            'user_id' => $this->pointsUserId,
            'points' => $this->pointsAmount,
            'reason' => $this->pointsReason,
        ]);
        
        $this->reset(['pointsUserId', 'pointsAmount', 'pointsReason']);
        $this->loadData();
    }
}; ?>

<div>
    <div class="flex flex-col gap-8 w-full max-w-6xl mx-auto">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Team Leaderboard</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Reward your team with badges and contribution points.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Leaderboard -->
            <div class="{{ $this->canManageLeaderboard() ? 'md:col-span-2' : 'md:col-span-3 max-w-4xl mx-auto w-full' }} bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center gap-2">
                    <flux:icon.trophy class="size-5 text-yellow-500" />
                    Top Contributors
                </h2>
                
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border-t border-zinc-100 dark:border-zinc-800">
                    @forelse($users as $index => $user)
                        <div class="py-4 flex justify-between items-center">
                            <div class="flex items-center gap-4">
                                <span class="text-xl font-bold text-zinc-300 dark:text-zinc-600 w-6 text-right">#{{ $index + 1 }}</span>
                                <flux:avatar size="sm" :name="$user->name" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-zinc-500 flex gap-1 mt-1">
                                        @foreach($user->badges as $badge)
                                            <span class="inline-block px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-[10px] uppercase font-bold" title="{{ $badge->name }}">{{ $badge->icon }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $user->total_points ?? 0 }}</span>
                                <span class="text-xs text-zinc-500 block">pts</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-zinc-500">No users found.</div>
                    @endforelse
                </div>
            </div>

            @if($this->canManageLeaderboard())
            <div class="space-y-6">
                <!-- Award Points -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Award Points</h2>
                    <form wire:submit="addPoints" class="space-y-4">
                        <flux:select wire:model="pointsUserId" label="User">
                            <option value="">Select user...</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:input wire:model="pointsAmount" type="number" label="Points (+/-)" />
                        
                        <flux:input wire:model="pointsReason" label="Reason" placeholder="e.g. Fixed critical bug" />
                        
                        <flux:button type="submit" variant="primary" class="w-full">Award Points</flux:button>
                    </form>
                </div>
                
                <!-- Assign Badge -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Assign Badge</h2>
                    <form wire:submit="assignBadge" class="space-y-4">
                        <flux:select wire:model="selectedUserId" label="User">
                            <option value="">Select user...</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:select wire:model="selectedBadgeId" label="Badge">
                            <option value="">Select badge...</option>
                            @foreach($badges as $b)
                                <option value="{{ $b->id }}">{{ $b->icon }} {{ $b->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:button type="submit" variant="primary" class="w-full">Assign Badge</flux:button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
