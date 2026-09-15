<?php

use Livewire\Volt\Component;
use App\Models\WhatsAppNotification;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;
    
    public function with()
    {
        return [
            'messages' => WhatsAppNotification::orderBy('created_at', 'desc')->paginate(20),
            'pendingCount' => WhatsAppNotification::where('status', 'pending')->count(),
            'failedCount' => WhatsAppNotification::where('status', 'failed')->count(),
        ];
    }
    
    public function processQueue()
    {
        // Mock processing
        $pending = WhatsAppNotification::where('status', 'pending')->get();
        foreach ($pending as $msg) {
            $msg->update(['status' => 'sent', 'provider_message_id' => 'MSG_'.uniqid()]);
        }
        
        Flux::toast(count($pending) . ' messages processed successfully.', variant: 'success');
    }
    
    public function retryFailed()
    {
        // Mock retry
        $failed = WhatsAppNotification::where('status', 'failed')->get();
        foreach ($failed as $msg) {
            $msg->update(['status' => 'pending']);
        }
        
        Flux::toast(count($failed) . ' failed messages moved to queue.', variant: 'success');
        
        // Immediately process them for demo
        $this->processQueue();
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">WhatsApp Control Center</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Monitor and manage outbound WhatsApp messages.</p>
            </div>
            
            <div class="flex items-center gap-2">
                @if($failedCount > 0)
                    <flux:button wire:click="retryFailed" variant="danger" icon="arrow-path">Retry Failed ({{ $failedCount }})</flux:button>
                @endif
                <flux:button wire:click="processQueue" variant="primary" icon="paper-airplane" :disabled="$pendingCount === 0">
                    Process Queue ({{ $pendingCount }})
                </flux:button>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-4 font-medium">Recipient</th>
                            <th class="px-6 py-4 font-medium w-full">Message Snippet</th>
                            <th class="px-6 py-4 font-medium">Status</th>
                            <th class="px-6 py-4 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($messages as $msg)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                    {{ $msg->recipient_phone }}
                                </td>
                                <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400 max-w-md truncate">
                                    {{ Str::limit($msg->message, 50) }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $color = match($msg->status) {
                                            'sent' => 'green',
                                            'pending' => 'amber',
                                            'failed' => 'red',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$color">{{ ucfirst($msg->status) }}</flux:badge>
                                </td>
                                <td class="px-6 py-4 text-zinc-500 text-xs">
                                    {{ $msg->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.chat-bubble-bottom-center-text class="size-8 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                                    No WhatsApp messages found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($messages->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
