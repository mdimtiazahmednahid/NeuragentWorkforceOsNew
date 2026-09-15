<?php

use Livewire\Volt\Component;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

new class extends Component {
    public $projects = [];
    public $users = [];
    
    // Each row represents a task
    public $rows = [];

    public function mount()
    {
        $this->projects = Project::where('status', 'ACTIVE')->get();
        
        if (auth()->user()->isLead) {
            $this->users = User::where('is_active', true)->orderBy('name')->get();
        } else {
            $this->users = User::where('id', auth()->id())->get();
        }
        // Start with 3 empty rows
        $this->addRows(3);
    }

    public function addRows($count = 1)
    {
        $defaultProjectId = $this->projects->first()?->id ?? '';
        $defaultAssigneeId = Auth::id();

        for ($i = 0; $i < $count; $i++) {
            $this->rows[] = [
                'title' => '',
                'project_id' => $defaultProjectId,
                'assignee_id' => $defaultAssigneeId,
                'priority' => 'MEDIUM',
                'status' => 'TODO',
                'task_type' => 'GENERAL',
                'risk_level' => 'LOW',
                'estimated_hours' => '',
                'deadline' => '',
            ];
        }
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        
        if (count($this->rows) === 0) {
            $this->addRows(1);
        }
    }

    public function saveTasks()
    {
        $savedCount = 0;
        
        foreach ($this->rows as $index => $row) {
            // Only save rows that have at least a title and project
            if (empty(trim($row['title'])) || empty($row['project_id'])) {
                continue;
            }
            
            Task::create([
                'title' => $row['title'],
                'project_id' => $row['project_id'],
                'assignee_id' => !empty($row['assignee_id']) ? $row['assignee_id'] : Auth::id(),
                'priority' => $row['priority'],
                'status' => $row['status'],
                'task_type' => $row['task_type'],
                'risk_level' => $row['risk_level'],
                'estimated_hours' => !empty($row['estimated_hours']) ? $row['estimated_hours'] : null,
                'deadline' => !empty($row['deadline']) ? $row['deadline'] : null,
                'created_by' => Auth::id(),
            ]);
            
            $savedCount++;
            
            // Clear the saved row
            $this->rows[$index] = [
                'title' => '',
                'project_id' => '',
                'assignee_id' => '',
                'priority' => 'MEDIUM',
                'status' => 'TODO',
                'task_type' => 'GENERAL',
                'risk_level' => 'LOW',
                'estimated_hours' => '',
                'deadline' => '',
            ];
        }
        
        if ($savedCount > 0) {
            Flux::toast('Saved ' . $savedCount . ' tasks successfully.', variant: 'success');
            $this->dispatch('tasks-updated');
        } else {
            Flux::toast('No valid tasks to save. Title and Project are required.', variant: 'warning');
        }
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-800">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold w-12 text-center">#</th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[250px]">Task Title <span class="text-red-500">*</span></th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[160px]">Project <span class="text-red-500">*</span></th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[150px]">Assignee</th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[130px]">Category <span class="text-red-500">*</span></th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[120px]">Priority <span class="text-red-500">*</span></th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[120px]">Risk <span class="text-red-500">*</span></th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[130px]">Status <span class="text-red-500">*</span></th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[140px]">Due Date</th>
                    <th scope="col" class="px-4 py-3 font-semibold min-w-[100px]">Est. Hrs</th>
                    <th scope="col" class="px-4 py-3 font-semibold w-16 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $row)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-0 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="px-4 py-2 text-center text-zinc-400 font-medium">
                            {{ $index + 1 }}
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" wire:model.defer="rows.{{ $index }}.title" placeholder="What needs to be done?" 
                                class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 placeholder-zinc-400">
                        </td>
                        <td class="px-4 py-2">
                            <select wire:model.defer="rows.{{ $index }}.project_id" class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 text-sm">
                                <option value="">Select Project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <select wire:model.defer="rows.{{ $index }}.assignee_id" class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 text-sm">
                                <option value="">Assign To...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <select wire:model.defer="rows.{{ $index }}.task_type" class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 text-sm">
                                <option value="GENERAL">General</option>
                                <option value="FEATURE">Feature</option>
                                <option value="BUG">Bug</option>
                                <option value="DOCS">Documentation</option>
                                <option value="DESIGN">Design</option>
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <select wire:model.defer="rows.{{ $index }}.priority" class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 text-sm">
                                <option value="LOW">Low</option>
                                <option value="MEDIUM">Medium</option>
                                <option value="HIGH">High</option>
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <select wire:model.defer="rows.{{ $index }}.risk_level" class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 text-sm">
                                <option value="LOW">Low</option>
                                <option value="MEDIUM">Medium</option>
                                <option value="HIGH">High</option>
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <select wire:model.defer="rows.{{ $index }}.status" class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 text-sm">
                                <option value="TODO">To Do</option>
                                <option value="IN_PROGRESS">In Progress</option>
                                <option value="DONE">Done</option>
                            </select>
                        </td>
                        <td class="px-4 py-2">
                            <input type="date" wire:model.defer="rows.{{ $index }}.deadline" 
                                class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 placeholder-zinc-400 text-sm">
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" step="0.5" min="0" wire:model.defer="rows.{{ $index }}.estimated_hours" placeholder="e.g. 4" 
                                class="w-full bg-transparent border-0 border-b border-transparent hover:border-zinc-300 focus:border-indigo-500 focus:ring-0 px-0 py-1 transition-colors dark:text-white dark:hover:border-zinc-600 placeholder-zinc-400 text-sm text-center">
                        </td>
                        <td class="px-4 py-2 text-center">
                            <button wire:click="removeRow({{ $index }})" type="button" class="text-zinc-400 hover:text-red-500 transition-colors p-1 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20" title="Remove Row">
                                <flux:icon.trash class="size-4" />
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="bg-zinc-50 dark:bg-zinc-800/50 px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
        <flux:button wire:click="addRows(1)" variant="ghost" size="sm" icon="plus">Add Row</flux:button>
        <flux:button wire:click="saveTasks" variant="primary">Save All Tasks</flux:button>
    </div>
</div>
