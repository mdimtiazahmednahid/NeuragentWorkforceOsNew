<?php

use Livewire\Volt\Component;
use App\Models\ActivityLog;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    
    public function with()
    {
        return [
            'logs' => ActivityLog::with('user')->orderBy('created_at', 'desc')->paginate(30),
        ];
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Audit Logs</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Review chronological system activity and administrative actions.</p>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-4 font-medium">Timestamp</th>
                            <th class="px-6 py-4 font-medium">User</th>
                            <th class="px-6 py-4 font-medium">Action</th>
                            <th class="px-6 py-4 font-medium w-full">Details</th>
                            <th class="px-6 py-4 font-medium">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($logs as $log)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 text-zinc-500 text-xs">
                                    {{ $log->created_at->format('M d, Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                    @if($log->user)
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="sm" :name="$log->user->name" />
                                            <span>{{ $log->user->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-zinc-400 italic">System</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-md text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 uppercase">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $log->description }}
                                </td>
                                <td class="px-6 py-4 text-zinc-400 font-mono text-xs">
                                    {{ $log->ip_address ?? 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.clipboard-document-list class="size-8 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" />
                                    No audit logs recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
