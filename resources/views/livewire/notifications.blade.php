<?php

use Livewire\Volt\Component;
use App\Models\Notification;

new class extends Component {
    public function with()
    {
        return [
            'notifications' => Notification::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->paginate(15)
        ];
    }
    
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())->update(['is_read' => true]);
        $this->dispatch('notifications-updated');
    }
    
    public function markAsRead($id)
    {
        Notification::where('user_id', auth()->id())->where('id', $id)->update(['is_read' => true]);
        $this->dispatch('notifications-updated');
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-4xl mx-auto">
        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Notifications</h1>
                <p class="text-zinc-500 dark:text-zinc-400">View all your alerts and updates.</p>
            </div>
            
            <flux:button wire:click="markAllAsRead" size="sm" variant="ghost">
                Mark all as read
            </flux:button>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col">
            <div class="flex-1 overflow-y-auto">
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($notifications as $notification)
                        <div class="p-5 flex gap-4 items-start {{ $notification->is_read ? 'opacity-70' : 'bg-blue-50/30 dark:bg-blue-900/10' }}">
                            <div class="mt-0.5">
                                @if($notification->type === 'SUCCESS')
                                    <flux:icon.check-circle class="size-6 text-green-500" />
                                @elseif($notification->type === 'WARNING')
                                    <flux:icon.exclamation-triangle class="size-6 text-yellow-500" />
                                @elseif($notification->type === 'DANGER')
                                    <flux:icon.x-circle class="size-6 text-red-500" />
                                @else
                                    <flux:icon.information-circle class="size-6 text-blue-500" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <h4 class="font-medium text-zinc-900 dark:text-white {{ !$notification->is_read ? 'font-bold' : '' }}">
                                        {{ $notification->title }}
                                    </h4>
                                    <span class="text-xs text-zinc-500 whitespace-nowrap ml-4">
                                        {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                    {{ $notification->message }}
                                </p>
                                
                                @if(!$notification->is_read)
                                    <div class="mt-3">
                                        <button wire:click="markAsRead({{ $notification->id }})" class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                            Mark as read
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-12 flex flex-col items-center justify-center text-center">
                            <flux:icon.bell-slash class="size-12 text-zinc-300 dark:text-zinc-600 mb-4" />
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">No notifications yet</h3>
                            <p class="text-zinc-500 dark:text-zinc-400 mt-1">When you get notifications, they'll show up here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            
            @if($notifications->hasPages())
                <div class="p-4 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
