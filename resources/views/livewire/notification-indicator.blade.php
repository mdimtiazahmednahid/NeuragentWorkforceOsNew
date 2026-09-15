<?php

use Livewire\Volt\Component;
use App\Models\Notification;
use Livewire\Attributes\On;

new class extends Component {
    public $hasUnread = false;

    public function mount()
    {
        $this->checkUnread();
    }

    #[On('notifications-updated')]
    public function checkUnread()
    {
        $this->hasUnread = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->exists();
    }
}; ?>

<div wire:poll.15s="checkUnread">
    <flux:button href="{{ route('notifications') }}" wire:navigate variant="ghost" size="sm" icon="bell" aria-label="Notifications" class="text-zinc-500 relative">
        @if($hasUnread)
            <div class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></div>
        @endif
    </flux:button>
</div>
