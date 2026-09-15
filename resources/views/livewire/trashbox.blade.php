<?php

use Livewire\Volt\Component;
use App\Models\Task;
use Flux\Flux;

new class extends Component {
    public $trashedTasks;
    
    public function mount()
    {
        $this->loadTrashedTasks();
    }
    
    public function loadTrashedTasks()
    {
        // Admin sees all trashed tasks, others see their own trashed tasks
        if (auth()->user()->isAdmin) {
            $this->trashedTasks = Task::onlyTrashed()->with(['assignee', 'project'])->orderBy('deleted_at', 'desc')->get();
        } else {
            $this->trashedTasks = Task::onlyTrashed()->with(['assignee', 'project'])
                ->where('assignee_id', auth()->id())
                ->orderBy('deleted_at', 'desc')
                ->get();
        }
    }
    
    public function restoreTask($taskId)
    {
        Task::onlyTrashed()->find($taskId)?->restore();
        Flux::toast('Task restored successfully.', variant: 'success');
        $this->loadTrashedTasks();
    }
    
    public function forceDeleteTask($taskId)
    {
        if (!auth()->user()->isAdmin) {
            Flux::toast('Only administrators can permanently delete tasks.', variant: 'danger');
            return;
        }
        
        Task::onlyTrashed()->find($taskId)?->forceDelete();
        Flux::toast('Task permanently deleted.', variant: 'success');
        $this->loadTrashedTasks();
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                    <flux:icon.trash class="size-6 text-zinc-400" />
                    Trashbox
                </h1>
                <p class="text-zinc-500 dark:text-zinc-400">View and restore deleted tasks.</p>
            </div>
            
            <div>
                <flux:button href="{{ route('tasks') }}" wire:navigate size="sm" variant="ghost" icon="arrow-left">Back to Tasks</flux:button>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">Task</th>
                            <th class="px-6 py-3 font-medium">Project</th>
                            <th class="px-6 py-3 font-medium">Deleted At</th>
                            <th class="px-6 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($trashedTasks as $task)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white line-through">{{ $task->title }}</div>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $task->project ? $task->project->name : 'No Project' }}
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $task->deleted_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:button wire:click="restoreTask({{ $task->id }})" size="sm" variant="ghost" icon="arrow-uturn-left" class="text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20" title="Restore" />
                                    @if(auth()->user()->isAdmin)
                                        <flux:button wire:click="forceDeleteTask({{ $task->id }})" wire:confirm="Are you sure? This cannot be undone." size="sm" variant="danger" icon="trash" class="ml-2" title="Permanently Delete" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-zinc-500">
                                        <flux:icon.trash class="size-12 mb-3 text-zinc-300 dark:text-zinc-700" />
                                        <p>The trashbox is empty.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
