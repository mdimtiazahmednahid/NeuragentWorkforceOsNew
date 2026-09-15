<?php

use Livewire\Volt\Component;
use App\Models\Task;
use Livewire\Attributes\On;

new class extends Component {
    public $tasks;
    public $view = 'list';
    
    protected $queryString = ['view'];
    
    public function mount()
    {
        $this->loadTasks();
    }
    
    #[On('tasks-updated')]
    public function loadTasks()
    {
        $this->tasks = Task::with(['assignee', 'project'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function updateStatus($taskId, $status)
    {
        Task::where('id', $taskId)->update(['status' => $status]);
        $this->loadTasks();
    }
    
    public function deleteTask($taskId)
    {
        Task::find($taskId)?->delete();
        $this->loadTasks();
    }
    
    public function setView($view)
    {
        $this->view = $view;
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">All Tasks</h1>
                <p class="text-zinc-500 dark:text-zinc-400">View and manage tasks across all projects.</p>
            </div>
            
            <div class="flex bg-zinc-100 dark:bg-zinc-800/50 p-1 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <button wire:click="setView('list')" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $view === 'list' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">List</button>
                <button wire:click="setView('spreadsheet')" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $view === 'spreadsheet' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">Spreadsheet</button>
                <button wire:click="setView('calendar')" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $view === 'calendar' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">Calendar</button>
            </div>
        </div>

        @if($view === 'list')
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Task</th>
                                <th class="px-6 py-3 font-medium">Project</th>
                                <th class="px-6 py-3 font-medium">Assignee</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($tasks as $task)
                                <tr>
                                    <td class="px-6 py-4">
                                        <flux:modal.trigger name="task-details">
                                            <div class="font-medium text-indigo-600 dark:text-indigo-400 cursor-pointer hover:underline" wire:click="$dispatch('openTaskDetails', { taskId: {{ $task->id }} })">
                                                <span class="text-xs text-zinc-500 mr-1.5 font-mono">{{ $task->task_id }}</span>{{ $task->title }}
                                            </div>
                                        </flux:modal.trigger>
                                        @if($task->deadline)
                                            <div class="text-xs text-red-500 mt-1">Due: {{ \Carbon\Carbon::parse($task->deadline)->format('M d') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                        {{ $task->project ? $task->project->name : 'No Project' }}
                                    </td>
                                    <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                        {{ $task->assignee ? $task->assignee->name : 'Unassigned' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            {{ $task->status === 'COMPLETED' || $task->status === 'DONE' ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' : '' }}
                                            {{ $task->status === 'IN_PROGRESS' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400' : '' }}
                                            {{ $task->status === 'TODO' ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' : '' }}
                                            {{ $task->status === 'BLOCKED' ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400' : '' }}
                                        ">
                                            {{ str_replace('_', ' ', $task->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <flux:modal.trigger name="task-details">
                                            <flux:button wire:click="$dispatch('openTaskDetails', { taskId: {{ $task->id }} })" size="sm" variant="ghost" icon="eye">View</flux:button>
                                        </flux:modal.trigger>
                                        @if($task->status !== 'DONE' && $task->status !== 'COMPLETED')
                                            <flux:button wire:click="updateStatus({{ $task->id }}, 'DONE')" size="sm" variant="primary" class="ml-2">Complete</flux:button>
                                        @else
                                            <flux:button wire:click="updateStatus({{ $task->id }}, 'TODO')" size="sm" variant="ghost" class="ml-2">Reopen</flux:button>
                                        @endif
                                        <flux:button wire:click="deleteTask({{ $task->id }})" size="sm" variant="danger" icon="trash" class="ml-2" title="Move to Trashbox" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-500">No tasks found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($view === 'spreadsheet')
            <livewire:spreadsheet-tasks />
        @elseif($view === 'calendar')
            <livewire:calendar-tasks />
        @endif
    </div>
    
    <livewire:task-details-modal />
</div>
