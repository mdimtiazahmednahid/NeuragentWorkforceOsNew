<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Badge;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $badges;
    public $users;
    public $awardHistory = [];
    
    // Create Badge state
    public $showCreateModal = false;
    public $name = '';
    public $iconType = 'preset';
    public $icon = 'star';
    public $customIcon = null;
    public $description = '';
    
    // Award Badge state
    public $showAwardModal = false;
    public $award_badge_id = '';
    public $award_user_id = '';
    
    public $availableIcons = [
        'star', 'trophy', 'fire', 'bolt', 'heart', 'academic-cap', 
        'beaker', 'check-badge', 'rocket-launch', 'sparkles',
    ];
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $this->badges = Badge::withCount('users')->get();
        $this->users = User::orderBy('name')->get();
        $this->awardHistory = \Illuminate\Support\Facades\DB::table('badge_user')
            ->join('users', 'badge_user.user_id', '=', 'users.id')
            ->join('badges', 'badge_user.badge_id', '=', 'badges.id')
            ->select('users.name as user_name', 'badges.name as badge_name', 'badges.icon as badge_icon', 'badge_user.created_at')
            ->orderBy('badge_user.created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function saveBadge()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'iconType' => 'required|in:preset,custom',
            'icon' => 'required_if:iconType,preset',
            'customIcon' => 'required_if:iconType,custom|nullable|image|max:2048',
            'description' => 'nullable|string',
        ]);
        
        $finalIcon = $this->icon;
        
        if ($this->iconType === 'custom' && $this->customIcon) {
            $finalIcon = $this->customIcon->store('badges', 'public');
        }
        
        Badge::create([
            'name' => $this->name,
            'icon' => $finalIcon,
            'description' => $this->description,
        ]);
        
        Flux::toast('Badge created successfully.', variant: 'success');
        $this->reset(['name', 'icon', 'description', 'showCreateModal', 'iconType', 'customIcon']);
        $this->loadData();
    }
    
    public function awardBadge()
    {
        $this->validate([
            'award_badge_id' => 'required|exists:badges,id',
            'award_user_id' => 'required|exists:users,id',
        ]);
        
        $user = User::find($this->award_user_id);
        
        if ($user->badges()->where('badge_id', $this->award_badge_id)->exists()) {
            Flux::toast('User already has this badge.', variant: 'danger');
            return;
        }
        
        $user->badges()->attach($this->award_badge_id);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'awarded',
            'model_type' => Badge::class,
            'model_id' => $this->award_badge_id,
            'description' => "Awarded badge to {$user->name}",
            'ip_address' => request()->ip(),
        ]);
        
        Flux::toast('Badge awarded successfully.', variant: 'success');
        $this->reset(['award_badge_id', 'award_user_id', 'showAwardModal']);
        $this->loadData();
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Badges & Awards</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Manage recognition badges and award them to team members.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <flux:button wire:click="$set('showAwardModal', true)" variant="ghost" icon="gift">Award Badge</flux:button>
                <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">New Badge</flux:button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse($badges as $badge)
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden p-5 flex flex-col items-center text-center group transition-all hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:shadow-md">
                    <div class="size-16 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4 group-hover:scale-110 transition-transform">
                        @if(str_contains($badge->icon, '/'))
                            <img src="{{ Storage::url($badge->icon) }}" class="size-10 object-contain rounded-lg" alt="{{ $badge->name }}" />
                        @else
                            <flux:icon icon="{{ $badge->icon ?? 'star' }}" class="size-8" />
                        @endif
                    </div>
                    
                    <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $badge->name }}</h3>
                    @if($badge->description)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">{{ $badge->description }}</p>
                    @endif
                    
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 w-full">
                        <span class="text-xs font-medium text-zinc-500">
                            Awarded to <strong class="text-zinc-900 dark:text-white">{{ $badge->users_count }}</strong> users
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-12 text-center text-zinc-500">
                    <flux:icon.star class="size-12 mx-auto mb-4 text-zinc-300 dark:text-zinc-700" />
                    <p>No badges created yet. Create one to start rewarding your team!</p>
                </div>
            @endforelse
        </div>
        
        <!-- Award History -->
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Recent Awards</h2>
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($awardHistory as $award)
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    @if(str_contains($award->badge_icon, '/'))
                                        <img src="{{ Storage::url($award->badge_icon) }}" class="size-6 object-contain rounded-sm" alt="{{ $award->badge_name }}" />
                                    @else
                                        <flux:icon icon="{{ $award->badge_icon ?? 'star' }}" class="size-5" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        {{ $award->user_name }} <span class="text-zinc-500 font-normal">earned the</span> {{ $award->badge_name }} <span class="text-zinc-500 font-normal">badge</span>
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm text-zinc-500">{{ \Carbon\Carbon::parse($award->created_at)->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-zinc-500">
                            No awards have been given out yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        
        <!-- Create Badge Modal -->
        <flux:modal wire:model="showCreateModal" class="md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Create New Badge</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Design a new recognition badge.</p>
                </div>

                <form wire:submit="saveBadge" class="space-y-4">
                    <flux:input wire:model="name" label="Badge Name" placeholder="e.g. Bug Hunter" />
                    
                    <flux:radio.group wire:model.live="iconType" label="Icon Source" class="mt-4 mb-2 flex gap-4">
                        <flux:radio value="preset" label="Preset Icons" />
                        <flux:radio value="custom" label="Upload Custom Icon" />
                    </flux:radio.group>
                    
                    @if($iconType === 'preset')
                        <div>
                            <flux:label>Badge Icon</flux:label>
                            <div class="grid grid-cols-5 gap-2 mt-2">
                                @foreach($availableIcons as $iconName)
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="icon" value="{{ $iconName }}" class="peer sr-only">
                                        <div class="p-3 border rounded-lg flex justify-center items-center peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-500/20 peer-checked:text-indigo-600 dark:peer-checked:text-indigo-400 text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                            <flux:icon icon="{{ $iconName }}" class="size-6" />
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div>
                            <flux:label>Upload Image</flux:label>
                            <input type="file" wire:model="customIcon" accept="image/*" class="mt-2 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-300">
                            @if ($customIcon)
                                <div class="mt-4 p-4 border rounded-lg flex justify-center items-center bg-zinc-50 dark:bg-zinc-800/50">
                                    <img src="{{ $customIcon->temporaryUrl() }}" class="size-16 object-contain rounded" />
                                </div>
                            @endif
                            @error('customIcon') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif
                    
                    <flux:textarea wire:model="description" label="Description" placeholder="What is this badge awarded for?" rows="3" />
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Create Badge</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
        
        <!-- Award Badge Modal -->
        <flux:modal wire:model="showAwardModal" class="md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Award Badge</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Recognize a team member's achievement.</p>
                </div>

                <form wire:submit="awardBadge" class="space-y-4">
                    <flux:select wire:model="award_badge_id" label="Select Badge">
                        <option value="">Choose a badge...</option>
                        @foreach($badges as $badge)
                            <option value="{{ $badge->id }}">{{ $badge->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model="award_user_id" label="Select User">
                        <option value="">Choose a user...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <flux:button wire:click="$set('showAwardModal', false)" variant="ghost">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Award</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    </div>
</div>
