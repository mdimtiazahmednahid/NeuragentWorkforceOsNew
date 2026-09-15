<?php

use Livewire\Volt\Component;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $task;
    
    // Edit state
    public $isEditing = false;
    
    // Editable fields
    public $title, $description, $status, $priority, $task_type, $risk_level;
    public $deadline, $start_date, $estimated_hours, $actual_hours, $progress;
    public $deliverables, $acceptance_criteria, $next_action, $blocker_description;
    
    // Comment state
    public $newComment = '';

    protected $listeners = ['openTaskDetails' => 'loadTask'];

    public function loadTask($taskId)
    {
        $this->task = Task::with(['assignee', 'creator', 'project', 'comments.user', 'attachments'])->findOrFail($taskId);
        
        $this->title = $this->task->title;
        $this->description = $this->task->description;
        $this->status = $this->task->status;
        $this->priority = $this->task->priority;
        $this->task_type = $this->task->task_type;
        $this->risk_level = $this->task->risk_level;
        $this->deadline = $this->task->deadline ? $this->task->deadline->format('Y-m-d') : null;
        $this->start_date = $this->task->start_date ? $this->task->start_date->format('Y-m-d') : null;
        $this->estimated_hours = $this->task->estimated_hours;
        $this->actual_hours = $this->task->actual_hours;
        $this->progress = $this->task->progress;
        $this->deliverables = $this->task->deliverables;
        $this->acceptance_criteria = $this->task->acceptance_criteria;
        $this->next_action = $this->task->next_action;
        $this->blocker_description = $this->task->blocker_description;
        
        $this->isEditing = false;
    }

    public function toggleEdit()
    {
        $this->isEditing = !$this->isEditing;
    }

    public function saveChanges()
    {
        $this->task->update([
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'task_type' => $this->task_type,
            'risk_level' => $this->risk_level,
            'deadline' => $this->deadline,
            'start_date' => $this->start_date,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'progress' => $this->progress,
            'deliverables' => $this->deliverables,
            'acceptance_criteria' => $this->acceptance_criteria,
            'next_action' => $this->next_action,
            'blocker_description' => $this->blocker_description,
        ]);
        
        $this->isEditing = false;
        $this->loadTask($this->task->id);
        $this->dispatch('tasks-updated');
    }

    public function addComment()
    {
        if (empty(trim($this->newComment))) return;
        
        TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => Auth::id(),
            'content' => $this->newComment
        ]);
        
        $this->newComment = '';
        $this->loadTask($this->task->id);
    }
}; ?>

<div>
    <flux:modal name="task-details" class="md:w-[800px] max-w-4xl">
        @if($task)
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-mono text-zinc-500">{{ $task->task_id }}</span>
                        <flux:badge size="sm" variant="{{ $task->status === 'DONE' ? 'success' : 'primary' }}">{{ str_replace('_', ' ', $task->status) }}</flux:badge>
                        <flux:badge size="sm" variant="outline">{{ $task->project?->name }}</flux:badge>
                    </div>
                    @if($isEditing)
                        <flux:input wire:model="title" class="text-xl font-bold mt-2" />
                    @else
                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $task->title }}</h2>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($isEditing)
                        <flux:button wire:click="toggleEdit" variant="ghost" size="sm">Cancel</flux:button>
                        <flux:button wire:click="saveChanges" variant="primary" size="sm">Save</flux:button>
                    @else
                        <flux:button wire:click="toggleEdit" variant="ghost" size="sm" icon="pencil-square">Edit</flux:button>
                    @endif
                    <flux:modal.close>
                        <flux:button variant="ghost" size="sm" icon="x-mark" />
                    </flux:modal.close>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left Column: Details -->
                <div class="col-span-2 space-y-6">
                    <div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-2">Description</h3>
                        @if($isEditing)
                            <flux:textarea wire:model="description" rows="4" placeholder="Add a description..." />
                        @else
                            <div class="text-sm text-zinc-600 dark:text-zinc-300 prose prose-sm dark:prose-invert">
                                {!! nl2br(e($task->description ?: 'No description provided.')) !!}
                            </div>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 border-t border-zinc-100 dark:border-zinc-800 pt-4">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-2">Acceptance Criteria</h3>
                            @if($isEditing)
                                <flux:textarea wire:model="acceptance_criteria" rows="3" />
                            @else
                                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {!! nl2br(e($task->acceptance_criteria ?: 'None.')) !!}
                                </div>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-2">Deliverables</h3>
                            @if($isEditing)
                                <flux:textarea wire:model="deliverables" rows="3" />
                            @else
                                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                    {!! nl2br(e($task->deliverables ?: 'None.')) !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-zinc-100 dark:border-zinc-800 pt-4">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-4">Comments & Updates</h3>
                        <div class="space-y-4 mb-4">
                            @forelse($task->comments as $comment)
                                <div class="flex gap-3">
                                    <flux:avatar size="sm" :name="$comment->user->name" />
                                    <div class="flex-1 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-3">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-medium">{{ $comment->user->name }}</span>
                                            <span class="text-xs text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $comment->content }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No comments yet.</p>
                            @endforelse
                        </div>
                        <div class="flex gap-2">
                            <flux:input wire:model="newComment" placeholder="Add a comment..." class="flex-1" />
                            <flux:button wire:click="addComment" variant="primary">Post</flux:button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Meta Fields -->
                <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Status</span>
                            @if($isEditing)
                                <flux:select wire:model="status" size="sm">
                                    <option value="TODO">To Do</option>
                                    <option value="IN_PROGRESS">In Progress</option>
                                    <option value="BLOCKED">Blocked</option>
                                    <option value="DONE">Done</option>
                                </flux:select>
                            @else
                                <span class="text-sm font-medium">{{ str_replace('_', ' ', $task->status) }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Priority</span>
                            @if($isEditing)
                                <flux:select wire:model="priority" size="sm">
                                    <option value="LOW">Low</option>
                                    <option value="MEDIUM">Medium</option>
                                    <option value="HIGH">High</option>
                                </flux:select>
                            @else
                                <span class="text-sm font-medium">{{ $task->priority }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Category</span>
                            @if($isEditing)
                                <flux:select wire:model="task_type" size="sm">
                                    <option value="GENERAL">General</option>
                                    <option value="FEATURE">Feature</option>
                                    <option value="BUG">Bug</option>
                                    <option value="DOCS">Docs</option>
                                </flux:select>
                            @else
                                <span class="text-sm font-medium">{{ $task->task_type }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Risk Level</span>
                            @if($isEditing)
                                <flux:select wire:model="risk_level" size="sm">
                                    <option value="LOW">Low</option>
                                    <option value="MEDIUM">Medium</option>
                                    <option value="HIGH">High</option>
                                </flux:select>
                            @else
                                <span class="text-sm font-medium">{{ $task->risk_level }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Assignee</span>
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :name="$task->assignee?->name" />
                                <span class="text-sm font-medium">{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Reporter</span>
                            <div class="flex items-center gap-2">
                                <flux:avatar size="xs" :name="$task->creator?->name" />
                                <span class="text-sm font-medium">{{ $task->creator?->name ?? 'System' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Start Date</span>
                            @if($isEditing)
                                <flux:input type="date" wire:model="start_date" size="sm" />
                            @else
                                <span class="text-sm">{{ $task->start_date ? $task->start_date->format('M d, Y') : '-' }}</span>
                            @endif
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Due Date</span>
                            @if($isEditing)
                                <flux:input type="date" wire:model="deadline" size="sm" />
                            @else
                                <span class="text-sm {{ $task->deadline && $task->deadline->isPast() ? 'text-red-500' : '' }}">{{ $task->deadline ? $task->deadline->format('M d, Y') : '-' }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Est. Hours</span>
                            @if($isEditing)
                                <flux:input type="number" wire:model="estimated_hours" size="sm" />
                            @else
                                <span class="text-sm">{{ $task->estimated_hours ?? '-' }}h</span>
                            @endif
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-zinc-500 mb-1">Progress</span>
                            @if($isEditing)
                                <flux:input type="number" min="0" max="100" wire:model="progress" size="sm" />
                            @else
                                <span class="text-sm">{{ $task->progress ?? 0 }}%</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="p-8 text-center text-zinc-500">
                Loading task details...
            </div>
        @endif
    </flux:modal>
</div>
