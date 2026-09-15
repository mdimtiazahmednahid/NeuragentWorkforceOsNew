<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $users = [];
    public $isImpersonating = false;
    public $originalUser = null;
    public $canSwitch = false;

    public function mount()
    {
        $this->isImpersonating = session()->has('original_user_id');
        if ($this->isImpersonating) {
            $this->originalUser = User::find(session('original_user_id'));
        }
        
        $this->canSwitch = auth()->user()->isAdmin || $this->isImpersonating;
        
        if ($this->canSwitch) {
            $this->users = User::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function switchUser($userId)
    {
        if ($userId == auth()->id()) return;
        
        // Handle Switch Back
        if ($this->isImpersonating && $userId == session('original_user_id')) {
            Auth::loginUsingId(session('original_user_id'));
            session()->forget('original_user_id');
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }
        
        // Handle Switch to New User
        if (!auth()->user()->isAdmin) {
            return;
        }
        
        if (!$this->isImpersonating) {
            session(['original_user_id' => auth()->id()]);
        }
        
        Auth::loginUsingId($userId);
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="inline-flex items-center">
    @if($canSwitch)
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" size="sm" icon="users" aria-label="Switch Account" class="text-zinc-500" />
            
            <flux:menu class="max-h-96 overflow-y-auto w-64">
                <div class="px-3 py-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-100 dark:border-zinc-800 mb-1">Switch Account</div>
                
                @if($isImpersonating && $originalUser)
                    <flux:menu.item wire:click="switchUser('{{ $originalUser->id }}')" icon="arrow-uturn-left" class="text-rose-600 dark:text-rose-400 font-medium">
                        Return to {{ $originalUser->name }}
                    </flux:menu.item>
                    <flux:menu.separator />
                @endif
                
                @foreach($users as $user)
                    <flux:menu.item wire:click="switchUser('{{ $user->id }}')" :disabled="$user->id == auth()->id()">
                        <div class="flex items-center gap-3 w-full">
                            <div class="size-6 rounded-full overflow-hidden shrink-0 border border-zinc-200 dark:border-zinc-800">
                                <img src="{{ $user->avatar ? Storage::url($user->avatar) : '' }}" class="size-full object-cover {{ !$user->avatar ? 'hidden' : '' }}" />
                                <flux:avatar size="xs" :initials="$user->initials()" class="{{ $user->avatar ? 'hidden' : '' }}" />
                            </div>
                            <div class="flex flex-col truncate">
                                <span class="font-medium text-sm leading-tight text-zinc-900 dark:text-white truncate">{{ $user->name }}</span>
                                <span class="text-xs text-zinc-500 font-mono mt-0.5 truncate">{{ $user->role }}</span>
                            </div>
                            @if($user->id == auth()->id())
                                <flux:icon.check-circle class="size-4 text-emerald-500 ml-auto shrink-0" />
                            @endif
                        </div>
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    @endif
</div>
