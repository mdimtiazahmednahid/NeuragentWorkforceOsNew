<?php

use Livewire\Volt\Component;
use App\Models\Message;

new class extends Component {
    public $unreadCount = 0;
    public $lastCheckedCount = 0;

    public function mount()
    {
        $this->checkUnread();
    }

    public function checkUnread()
    {
        $count = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->count();
            
        if ($count > $this->unreadCount) {
            $this->dispatch('play-message-sound');
        }
        
        $this->unreadCount = $count;
        $this->lastCheckedCount = $count;
    }
}; ?>

<div wire:poll.10s="checkUnread" class="relative">
    <flux:button wire:click="$dispatch('openGlobalChat')" variant="ghost" size="sm" icon="chat-bubble-oval-left-ellipsis" aria-label="Messenger" class="text-zinc-500 relative cursor-pointer" />
    
    @if($unreadCount > 0)
        <div class="absolute top-0 right-0 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-[9px] font-bold text-white pointer-events-none transform translate-x-1/4 -translate-y-1/4 shadow-sm">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('play-message-sound', () => {
                // Play a subtle notification sound
                try {
                    const audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                    audio.volume = 0.5;
                    audio.play();
                } catch (e) {
                    console.error("Audio playback blocked", e);
                }
            });
        });
    </script>
</div>
