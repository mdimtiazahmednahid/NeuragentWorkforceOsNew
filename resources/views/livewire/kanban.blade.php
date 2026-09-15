<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public Project $project;
    
    // Create task form
    public $showCreateModal = false;
    public $newTaskTitle = '';
    public $newTaskDescription = '';
    public $newTaskStatus = 'TODO';
    public $newTaskPriority = 'MEDIUM';
    public $newTaskAssignee = '';
    
    // Comprehensive fields
    public $newTaskType = 'GENERAL';
    public $newTaskRiskLevel = 'LOW';
    public $newTaskReviewer = '';
    public $newTaskStartDate = '';
    public $newTaskDeadline = '';
    public $newTaskEstimatedHours = '';
    public $newTaskDeliverables = '';
    public $newTaskAcceptanceCriteria = '';
    
    public $users = [];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        
        if (auth()->user()->isLead) {
            $this->users = User::all();
        } else {
            $this->users = User::where('id', auth()->id())->get();
        }
        
        $this->newTaskAssignee = auth()->id();
    }

    public function with()
    {
        return [
            'tasksTodo' => Task::with('assignee')->where('project_id', $this->project->id)->where('status', 'TODO')->orderBy('id', 'desc')->get(),
            'tasksInProgress' => Task::with('assignee')->where('project_id', $this->project->id)->whereIn('status', ['STARTED', 'IN_PROGRESS'])->orderBy('id', 'desc')->get(),
            'tasksBlocked' => Task::with('assignee')->where('project_id', $this->project->id)->whereIn('status', ['PAUSED', 'BLOCKED'])->orderBy('id', 'desc')->get(),
            'tasksReview' => Task::with('assignee')->where('project_id', $this->project->id)->whereIn('status', ['WAITING_FOR_REVIEW', 'REVISION_NEEDED'])->orderBy('id', 'desc')->get(),
            'tasksDone' => Task::with('assignee')->where('project_id', $this->project->id)->where('status', 'COMPLETED')->orderBy('id', 'desc')->get(),
        ];
    }
    
    public function createTask()
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
        ]);
        
        Task::create([
            'title' => $this->newTaskTitle,
            'description' => $this->newTaskDescription,
            'status' => $this->newTaskStatus,
            'priority' => $this->newTaskPriority,
            'assignee_id' => $this->newTaskAssignee ?: null,
            'project_id' => $this->project->id,
            'created_by' => Auth::id(),
            'task_type' => $this->newTaskType ?: 'GENERAL',
            'risk_level' => $this->newTaskRiskLevel ?: 'LOW',
            'reviewer_id' => $this->newTaskReviewer ?: null,
            'start_date' => $this->newTaskStartDate ?: null,
            'deadline' => $this->newTaskDeadline ?: null,
            'estimated_hours' => $this->newTaskEstimatedHours ?: null,
            'deliverables' => $this->newTaskDeliverables,
            'acceptance_criteria' => $this->newTaskAcceptanceCriteria,
        ]);
        
        $this->reset([
            'newTaskTitle', 'newTaskDescription', 'newTaskStatus', 'newTaskPriority', 
            'newTaskAssignee', 'showCreateModal', 'newTaskType', 'newTaskRiskLevel', 
            'newTaskReviewer', 'newTaskStartDate', 'newTaskDeadline', 'newTaskEstimatedHours', 
            'newTaskDeliverables', 'newTaskAcceptanceCriteria'
        ]);
    }
    
    public function updateTaskStatus($taskId, $newGroup)
    {
        $statusMap = [
            'TODO' => 'TODO',
            'IN_PROGRESS' => 'IN_PROGRESS',
            'BLOCKED' => 'BLOCKED',
            'REVIEW' => 'WAITING_FOR_REVIEW',
            'DONE' => 'COMPLETED',
        ];
        
        $newStatus = $statusMap[$newGroup] ?? 'TODO';
        
        Task::where('id', $taskId)->where('project_id', $this->project->id)->update([
            'status' => $newStatus
        ]);
    }
}; ?>

<div>
    <div class="flex flex-col h-[calc(100vh-8rem)] w-full">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('projects') }}" wire:navigate class="text-zinc-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        <flux:icon.folder class="size-5" />
                    </a>
                    <span class="text-zinc-300 dark:text-zinc-600">/</span>
                    <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $project->name }}</h1>
                </div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Kanban Board</p>
            </div>
            
            <div class="flex items-center gap-2">
                <flux:button wire:click="$dispatch('openGlobalChatToProject', { projectId: {{ $project->id }} })" variant="subtle" icon="chat-bubble-left-right" class="hidden sm:flex">Team Chat</flux:button>
                <flux:modal.trigger name="create-task">
                    <flux:button variant="primary" icon="plus">New Task</flux:button>
                </flux:modal.trigger>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="flex-1 overflow-x-auto overflow-y-hidden pb-4">
            <div class="flex gap-6 h-full min-w-max items-start">
                
                <!-- Column: To Do -->
                <div class="w-80 flex flex-col max-h-full bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center bg-white dark:bg-zinc-900 rounded-t-xl">
                        <h3 class="font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                            <span class="size-2 rounded-full bg-zinc-400"></span> To Do
                        </h3>
                        <span class="text-xs font-medium text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded-full">{{ $tasksTodo->count() }}</span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3" x-sort="item => $wire.updateTaskStatus(item, 'TODO')" x-sort:group="kanban">
                        @foreach($tasksTodo as $task)
                            <div x-sort:item="{{ $task->id }}" class="bg-white dark:bg-zinc-900 p-4 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 cursor-grab active:cursor-grabbing hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors group">
                                <div class="flex justify-between items-start mb-2">
                                    <flux:badge size="sm" :variant="$task->priority === 'HIGH' || $task->priority === 'URGENT' || $task->priority === 'CRITICAL' ? 'danger' : 'success'">
                                        {{ $task->priority }}
                                    </flux:badge>
                                    <span class="text-xs text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">#{{ $task->id }}</span>
                                </div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-2 leading-tight">{{ $task->title }}</h4>
                                @if($task->assignee)
                                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                        <flux:avatar size="xs" />
                                        <span class="text-xs text-zinc-500">{{ $task->assignee->name }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Column: In Progress -->
                <div class="w-80 flex flex-col max-h-full bg-indigo-50/30 dark:bg-indigo-900/10 rounded-xl border border-indigo-100 dark:border-indigo-900/30">
                    <div class="p-4 border-b border-indigo-100 dark:border-indigo-900/50 flex justify-between items-center bg-indigo-50/50 dark:bg-indigo-900/20 rounded-t-xl">
                        <h3 class="font-semibold text-indigo-900 dark:text-indigo-300 flex items-center gap-2">
                            <span class="size-2 rounded-full bg-indigo-500"></span> In Progress
                        </h3>
                        <span class="text-xs font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/50 px-2 py-1 rounded-full">{{ $tasksInProgress->count() }}</span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3" x-sort="item => $wire.updateTaskStatus(item, 'IN_PROGRESS')" x-sort:group="kanban">
                        @foreach($tasksInProgress as $task)
                            <div x-sort:item="{{ $task->id }}" class="bg-white dark:bg-zinc-900 p-4 rounded-lg shadow-sm border border-indigo-200 dark:border-indigo-800 cursor-grab active:cursor-grabbing hover:border-indigo-400 transition-colors group">
                                <div class="flex justify-between items-start mb-2">
                                    <flux:badge size="sm" :variant="$task->priority === 'HIGH' || $task->priority === 'URGENT' || $task->priority === 'CRITICAL' ? 'danger' : 'success'">
                                        {{ $task->priority }}
                                    </flux:badge>
                                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $task->status }}</span>
                                </div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-2 leading-tight">{{ $task->title }}</h4>
                                @if($task->assignee)
                                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                        <flux:avatar size="xs" />
                                        <span class="text-xs text-zinc-500">{{ $task->assignee->name }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Column: Blocked/Paused -->
                <div class="w-80 flex flex-col max-h-full bg-rose-50/30 dark:bg-rose-900/10 rounded-xl border border-rose-100 dark:border-rose-900/30">
                    <div class="p-4 border-b border-rose-100 dark:border-rose-900/50 flex justify-between items-center bg-rose-50/50 dark:bg-rose-900/20 rounded-t-xl">
                        <h3 class="font-semibold text-rose-900 dark:text-rose-300 flex items-center gap-2">
                            <span class="size-2 rounded-full bg-rose-500"></span> Blocked / Paused
                        </h3>
                        <span class="text-xs font-medium text-rose-700 dark:text-rose-400 bg-rose-100 dark:bg-rose-900/50 px-2 py-1 rounded-full">{{ $tasksBlocked->count() }}</span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3" x-sort="item => $wire.updateTaskStatus(item, 'BLOCKED')" x-sort:group="kanban">
                        @foreach($tasksBlocked as $task)
                            <div x-sort:item="{{ $task->id }}" class="bg-white dark:bg-zinc-900 p-4 rounded-lg shadow-sm border border-rose-200 dark:border-rose-800 cursor-grab active:cursor-grabbing hover:border-rose-400 transition-colors group">
                                <div class="flex justify-between items-start mb-2">
                                    <flux:badge size="sm" :variant="$task->priority === 'HIGH' || $task->priority === 'URGENT' || $task->priority === 'CRITICAL' ? 'danger' : 'success'">
                                        {{ $task->priority }}
                                    </flux:badge>
                                    <span class="text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $task->status }}</span>
                                </div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-2 leading-tight">{{ $task->title }}</h4>
                                @if($task->assignee)
                                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                        <flux:avatar size="xs" />
                                        <span class="text-xs text-zinc-500">{{ $task->assignee->name }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Column: Review -->
                <div class="w-80 flex flex-col max-h-full bg-amber-50/30 dark:bg-amber-900/10 rounded-xl border border-amber-100 dark:border-amber-900/30">
                    <div class="p-4 border-b border-amber-100 dark:border-amber-900/50 flex justify-between items-center bg-amber-50/50 dark:bg-amber-900/20 rounded-t-xl">
                        <h3 class="font-semibold text-amber-900 dark:text-amber-300 flex items-center gap-2">
                            <span class="size-2 rounded-full bg-amber-500"></span> In Review
                        </h3>
                        <span class="text-xs font-medium text-amber-700 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/50 px-2 py-1 rounded-full">{{ $tasksReview->count() }}</span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3" x-sort="item => $wire.updateTaskStatus(item, 'REVIEW')" x-sort:group="kanban">
                        @foreach($tasksReview as $task)
                            <div x-sort:item="{{ $task->id }}" class="bg-white dark:bg-zinc-900 p-4 rounded-lg shadow-sm border border-amber-200 dark:border-amber-800 cursor-grab active:cursor-grabbing hover:border-amber-400 transition-colors group">
                                <div class="flex justify-between items-start mb-2">
                                    <flux:badge size="sm" :variant="$task->priority === 'HIGH' || $task->priority === 'URGENT' || $task->priority === 'CRITICAL' ? 'danger' : 'success'">
                                        {{ $task->priority }}
                                    </flux:badge>
                                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">REVIEW</span>
                                </div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-2 leading-tight">{{ $task->title }}</h4>
                                @if($task->assignee)
                                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                        <flux:avatar size="xs" />
                                        <span class="text-xs text-zinc-500">{{ $task->assignee->name }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Column: Done -->
                <div class="w-80 flex flex-col max-h-full bg-green-50/30 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-900/30">
                    <div class="p-4 border-b border-green-100 dark:border-green-900/50 flex justify-between items-center bg-green-50/50 dark:bg-green-900/20 rounded-t-xl">
                        <h3 class="font-semibold text-green-900 dark:text-green-300 flex items-center gap-2">
                            <span class="size-2 rounded-full bg-green-500"></span> Completed
                        </h3>
                        <span class="text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/50 px-2 py-1 rounded-full">{{ $tasksDone->count() }}</span>
                    </div>
                    
                    <div class="p-3 flex-1 overflow-y-auto space-y-3" x-sort="item => $wire.updateTaskStatus(item, 'DONE')" x-sort:group="kanban">
                        @foreach($tasksDone as $task)
                            <div x-sort:item="{{ $task->id }}" class="bg-white dark:bg-zinc-900 p-4 rounded-lg shadow-sm border border-green-200 dark:border-green-800 cursor-grab active:cursor-grabbing hover:border-green-400 transition-colors group opacity-75 hover:opacity-100">
                                <div class="flex justify-between items-start mb-2">
                                    <flux:badge size="sm" :variant="$task->priority === 'HIGH' || $task->priority === 'URGENT' || $task->priority === 'CRITICAL' ? 'danger' : 'success'">
                                        {{ $task->priority }}
                                    </flux:badge>
                                </div>
                                <h4 class="font-medium text-zinc-500 dark:text-zinc-400 line-through mb-2 leading-tight">{{ $task->title }}</h4>
                                @if($task->assignee)
                                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                                        <flux:avatar size="xs" />
                                        <span class="text-xs text-zinc-500">{{ $task->assignee->name }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Spacer for scrolling -->
                <div class="w-8 shrink-0"></div>
            </div>
        </div>
        
        <!-- Create Task Modal -->
        <flux:modal name="create-task" class="md:w-[800px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Create Task</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Add a new comprehensive task to {{ $project->name }}.</p>
                </div>

                <form wire:submit="createTask" class="space-y-4 max-h-[70vh] overflow-y-auto px-1 pb-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <flux:input wire:model="newTaskTitle" label="Task Title *" placeholder="What needs to be done?" required />
                        </div>
                        
                        <div class="md:col-span-2">
                            <flux:textarea wire:model="newTaskDescription" label="Description" placeholder="Detailed task description..." rows="3" />
                        </div>

                        <flux:select wire:model="newTaskType" label="Category / Type *" required>
                            <option value="GENERAL">General</option>
                            <option value="FEATURE">Feature</option>
                            <option value="BUG">Bug</option>
                            <option value="MAINTENANCE">Maintenance</option>
                            <option value="RESEARCH">Research</option>
                        </flux:select>
                        
                        <flux:select wire:model="newTaskStatus" label="Status *" required>
                            <option value="TODO">To Do</option>
                            <option value="IN_PROGRESS">In Progress</option>
                            <option value="BLOCKED">Blocked</option>
                            <option value="REVIEW">Review</option>
                        </flux:select>

                        <flux:select wire:model="newTaskPriority" label="Priority *" required>
                            <option value="LOW">Low</option>
                            <option value="MEDIUM">Medium</option>
                            <option value="HIGH">High</option>
                            <option value="URGENT">Urgent</option>
                            <option value="CRITICAL">Critical</option>
                        </flux:select>

                        <flux:select wire:model="newTaskRiskLevel" label="Risk Level *" required>
                            <option value="LOW">Low</option>
                            <option value="MEDIUM">Medium</option>
                            <option value="HIGH">High</option>
                        </flux:select>

                        <flux:select wire:model="newTaskAssignee" label="Assignee">
                            <option value="">Unassigned</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:select wire:model="newTaskReviewer" label="Reporter / Reviewer">
                            <option value="">Unassigned</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="newTaskStartDate" type="date" label="Start Date" />
                        <flux:input wire:model="newTaskDeadline" type="date" label="Due Date" />
                        
                        <div class="md:col-span-2">
                            <flux:input wire:model="newTaskEstimatedHours" type="number" step="0.5" label="Estimated Hours" placeholder="e.g. 4.5" />
                        </div>
                        
                        <div class="md:col-span-2">
                            <flux:textarea wire:model="newTaskDeliverables" label="Deliverables" placeholder="What exactly needs to be delivered?" rows="2" />
                        </div>
                        
                        <div class="md:col-span-2">
                            <flux:textarea wire:model="newTaskAcceptanceCriteria" label="Acceptance Criteria" placeholder="How do we know this is done?" rows="2" />
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create Task</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    </div>
</div>
