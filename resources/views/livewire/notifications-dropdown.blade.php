<?php

use Livewire\Volt\Component;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $notifications = [];
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (Auth::check()) {
            $this->notifications = Notification::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            $this->unreadCount = Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->count();
        }
    }

    public function markAsRead($id)
    {
        Notification::where('id', $id)->where('user_id', Auth::id())->update(['is_read' => true]);
        $this->loadNotifications();
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);
        $this->loadNotifications();
    }
}; ?>

<flux:dropdown position="top" class="w-full">
    <div class="relative w-full">
        @if($unreadCount > 0)
            <flux:sidebar.item icon="bell-alert" class="w-full text-blue-600 dark:text-blue-400 font-medium bg-blue-50 dark:bg-blue-900/20">
                Notifications ({{ $unreadCount }})
            </flux:sidebar.item>
            <div class="absolute top-2 right-2 size-2.5 bg-red-500 rounded-full border border-white dark:border-zinc-900 animate-pulse"></div>
        @else
            <flux:sidebar.item icon="bell" class="w-full">
                Notifications
            </flux:sidebar.item>
        @endif
    </div>

    <flux:menu class="w-80 p-0 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800">
            <span class="font-semibold text-zinc-900 dark:text-white">Notifications</span>
            @if($unreadCount > 0)
                <button wire:click.stop="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">Mark all as read</button>
            @endif
        </div>
        
        <div class="max-h-[300px] overflow-y-auto">
            @forelse($notifications as $notification)
                <div 
                    wire:click.stop="markAsRead({{ $notification->id }})" 
                    class="px-4 py-3 border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition-colors {{ !$notification->is_read ? 'bg-indigo-50/30 dark:bg-indigo-900/10' : '' }}"
                >
                    <div class="flex gap-3">
                        <div class="mt-0.5">
                            @if($notification->type === 'INFO')
                                <flux:icon.information-circle class="size-5 text-blue-500" />
                            @elseif($notification->type === 'SUCCESS')
                                <flux:icon.check-circle class="size-5 text-green-500" />
                            @elseif($notification->type === 'WARNING')
                                <flux:icon.exclamation-triangle class="size-5 text-amber-500" />
                            @elseif($notification->type === 'DANGER')
                                <flux:icon.exclamation-circle class="size-5 text-red-500" />
                            @else
                                <flux:icon.bell class="size-5 text-zinc-400" />
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white mb-0.5 {{ !$notification->is_read ? 'font-bold' : '' }}">
                                {{ $notification->title }}
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">
                                {{ $notification->message }}
                            </p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">
                                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                            </p>
                        </div>
                        @if(!$notification->is_read)
                            <div class="size-2 bg-indigo-500 rounded-full mt-1.5 shrink-0"></div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-zinc-500 dark:text-zinc-400">
                    <flux:icon.bell class="size-8 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" />
                    <p class="text-sm">No notifications yet.</p>
                </div>
            @endforelse
        </div>
        
        <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-center">
            <a href="{{ route('notifications') }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                View all notifications
            </a>
        </div>
    </flux:menu>
</flux:dropdown>
