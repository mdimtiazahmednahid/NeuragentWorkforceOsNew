<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $projects;
    public $departments;
    
    // Modal states
    public $showCreateModal = false;
    
    // Create form fields
    public $name = '';
    public $description = '';
    public $department_id = '';
    public $priority = 'MEDIUM';
    public $status = 'PLANNED';
    
    // Edit form fields
    public $editProjectId = null;
    public $editName = '';
    public $editDescription = '';
    public $editDepartmentId = '';
    public $editPriority = '';
    public $editStatus = '';
    
    // Manage Members state
    public $manageProjectId = null;
    public $projectMembers = [];
    public $availableUsers = [];
    public $newMemberId = '';
    public $newMemberRole = 'MEMBER';

    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $this->departments = Department::all();
        $this->projects = Project::with(['owner', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function saveProject()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:LOW,MEDIUM,HIGH,URGENT,CRITICAL',
        ]);

        Project::create([
            'name' => $this->name,
            'description' => $this->description,
            'department_id' => $this->department_id,
            'priority' => $this->priority,
            'status' => 'PLANNED',
            'owner_id' => Auth::id(),
            'created_by' => Auth::id(),
        ]);

        $this->reset(['name', 'description', 'department_id', 'priority', 'showCreateModal']);
        $this->loadData();
    }
    
    public function editProject($projectId)
    {
        $project = Project::findOrFail($projectId);
        $this->editProjectId = $project->id;
        $this->editName = $project->name;
        $this->editDescription = $project->description;
        $this->editDepartmentId = $project->department_id;
        $this->editPriority = $project->priority;
        $this->editStatus = $project->status;
        
        \Flux\Flux::modal('edit-project')->show();
    }

    public function updateProject()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editDepartmentId' => 'required|exists:departments,id',
            'editPriority' => 'required|in:LOW,MEDIUM,HIGH,URGENT,CRITICAL',
            'editStatus' => 'required|string',
        ]);
        
        $project = Project::findOrFail($this->editProjectId);
        $project->update([
            'name' => $this->editName,
            'description' => $this->editDescription,
            'department_id' => $this->editDepartmentId,
            'priority' => $this->editPriority,
            'status' => $this->editStatus,
        ]);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model_type' => Project::class,
            'model_id' => $project->id,
            'description' => "Updated project {$project->name}",
            'ip_address' => request()->ip(),
        ]);
        
        \Flux\Flux::modal('edit-project')->close();
        \Flux\Flux::toast('Project updated successfully.', variant: 'success');
        $this->loadData();
    }

    public function deleteProject($projectId)
    {
        if (!auth()->user()->isProjectManager) {
            \Flux\Flux::toast('You do not have permission to delete projects.', variant: 'danger');
            return;
        }
        
        $project = Project::find($projectId);
        if ($project) {
            \App\Models\Task::where('project_id', $project->id)->forceDelete();
            $project->delete();
            
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'model_type' => Project::class,
                'model_id' => $projectId,
                'description' => "Deleted project {$project->name}",
                'ip_address' => request()->ip(),
            ]);
            
            \Flux\Flux::toast('Project deleted successfully.', variant: 'success');
            $this->loadData();
        }
    }
    
    public function openManageMembers($projectId)
    {
        $this->manageProjectId = $projectId;
        $this->loadMembers();
        $this->availableUsers = \App\Models\User::where('is_active', true)->orderBy('name')->get();
        \Flux\Flux::modal('manage-members')->show();
    }
    
    public function loadMembers()
    {
        if ($this->manageProjectId) {
            $this->projectMembers = \App\Models\ProjectMember::with('user')
                ->where('project_id', $this->manageProjectId)
                ->get();
        }
    }

    public function addMember()
    {
        $this->validate([
            'newMemberId' => 'required|exists:users,id',
            'newMemberRole' => 'required|string',
        ]);
        
        $exists = \App\Models\ProjectMember::where('project_id', $this->manageProjectId)
            ->where('user_id', $this->newMemberId)
            ->exists();
            
        if ($exists) {
            \Flux\Flux::toast('User is already a member of this project.', variant: 'warning');
            return;
        }
        
        \App\Models\ProjectMember::create([
            'project_id' => $this->manageProjectId,
            'user_id' => $this->newMemberId,
            'role' => $this->newMemberRole,
            'status' => 'ACTIVE'
        ]);
        
        $this->reset(['newMemberId']);
        $this->newMemberRole = 'MEMBER';
        $this->loadMembers();
        \Flux\Flux::toast('Member added successfully.', variant: 'success');
    }
    
    public function removeMember($memberId)
    {
        if (!auth()->user()->isProjectManager) {
            \Flux\Flux::toast('You do not have permission to remove members.', variant: 'danger');
            return;
        }
        
        \App\Models\ProjectMember::where('id', $memberId)->delete();
        $this->loadMembers();
        \Flux\Flux::toast('Member removed.', variant: 'success');
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Projects</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Manage all your organization's projects.</p>
            </div>
            
            <flux:modal.trigger name="create-project">
                <flux:button variant="primary" icon="plus">New Project</flux:button>
            </flux:modal.trigger>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($projects as $project)
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-6 flex flex-col hover:border-indigo-500 dark:hover:border-indigo-500 transition-colors shadow-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-2">
                            <span class="size-3 rounded-full 
                                @if($project->status === 'ACTIVE') bg-green-500
                                @elseif($project->status === 'COMPLETED') bg-indigo-500
                                @else bg-zinc-400 @endif
                            "></span>
                            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ $project->status }}</span>
                        </div>
                        <div class="flex items-center gap-1 -mr-2">
                            <flux:badge size="sm" :variant="$project->priority === 'HIGH' || $project->priority === 'URGENT' || $project->priority === 'CRITICAL' ? 'danger' : 'success'">
                                {{ $project->priority }}
                            </flux:badge>
                            
                            @if(auth()->user()->isProjectManager)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" class="text-zinc-400 hover:text-zinc-900 dark:hover:text-white px-1" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="openManageMembers({{ $project->id }})" icon="users">Manage Members</flux:menu.item>
                                        <flux:menu.item wire:click="editProject({{ $project->id }})" icon="pencil-square">Edit Project</flux:menu.item>
                                        <flux:menu.item wire:click="deleteProject({{ $project->id }})" wire:confirm="Are you sure you want to permanently delete this project? This will delete all associated tasks as well and cannot be undone." icon="trash" class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">Delete Project</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ $project->name }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 mb-6 flex-1">{{ $project->description ?: 'No description provided.' }}</p>
                    
                    <div class="flex justify-between items-center pt-4 border-t border-zinc-100 dark:border-zinc-800 mt-auto">
                        <div class="flex items-center gap-2">
                            <flux:avatar size="sm" />
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $project->owner?->name ?? 'Unassigned' }}</span>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <flux:modal.trigger name="project-docs">
                                <button wire:click="$dispatch('openProjectDocs', { projectId: {{ $project->id }} })" class="text-sm font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 flex items-center gap-1">
                                    <flux:icon.document-text class="size-4" /> Docs
                                </button>
                            </flux:modal.trigger>
                            
                            <a href="{{ route('kanban', ['project' => $project->id]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center gap-1">
                                Board <flux:icon.arrow-right class="size-4" />
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-zinc-900 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-12 text-center">
                    <flux:icon.folder class="size-12 mx-auto text-zinc-400 mb-4" />
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">No projects found</h3>
                    <p class="text-zinc-500 mt-1">Get started by creating a new project.</p>
                </div>
            @endforelse
        </div>

        <flux:modal name="create-project" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Create Project</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Add a new project to your workspace.</p>
                </div>

                <form wire:submit="saveProject" class="space-y-4">
                    <flux:input wire:model="name" label="Project Name" placeholder="e.g. Website Redesign" />
                    
                    <flux:textarea wire:model="description" label="Description" placeholder="Optional details..." />
                    
                    <flux:select wire:model="department_id" label="Department">
                        <option value="">Select a department...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model="priority" label="Priority">
                        <option value="LOW">Low</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="HIGH">High</option>
                        <option value="URGENT">Urgent</option>
                        <option value="CRITICAL">Critical</option>
                    </flux:select>
                    
                    <div class="flex justify-end gap-2 pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create Project</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>

        <flux:modal name="edit-project" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit Project</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Update project details.</p>
                </div>

                <form wire:submit="updateProject" class="space-y-4">
                    <flux:input wire:model="editName" label="Project Name" placeholder="e.g. Website Redesign" />
                    
                    <flux:textarea wire:model="editDescription" label="Description" placeholder="Optional details..." />
                    
                    <flux:select wire:model="editDepartmentId" label="Department">
                        <option value="">Select a department...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model="editStatus" label="Status">
                        <option value="PLANNED">Planned</option>
                        <option value="ACTIVE">Active</option>
                        <option value="PAUSED">Paused</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="CANCELLED">Cancelled</option>
                    </flux:select>

                    <flux:select wire:model="editPriority" label="Priority">
                        <option value="LOW">Low</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="HIGH">High</option>
                        <option value="URGENT">Urgent</option>
                        <option value="CRITICAL">Critical</option>
                    </flux:select>
                    
                    <div class="flex justify-end gap-2 pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Save Changes</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
        
        <flux:modal name="manage-members" class="md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Manage Members</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Add or remove users from this project.</p>
                </div>

                <!-- Add Member Form -->
                <form wire:submit="addMember" class="flex gap-2 items-end bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <flux:select wire:model="newMemberId" label="Select User">
                            <option value="">Choose a user...</option>
                            @foreach($availableUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="w-1/3">
                        <flux:input wire:model="newMemberRole" label="Role" placeholder="e.g. Developer, QA..." />
                    </div>
                    <flux:button type="submit" variant="primary">Add</flux:button>
                </form>
                
                <!-- Members List -->
                <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-800">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold">User</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Role</th>
                                <th scope="col" class="px-4 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projectMembers as $member)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-0 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <flux:avatar size="sm" />
                                            <div>
                                                <div class="font-medium text-zinc-900 dark:text-white">{{ $member->user->name }}</div>
                                                <div class="text-xs text-zinc-500">{{ $member->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:badge size="sm" class="uppercase">{{ $member->role }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button wire:click="removeMember({{ $member->id }})" variant="ghost" size="sm" class="text-rose-500 hover:text-rose-700 px-2" icon="trash" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-zinc-500">
                                        No members added yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="flex justify-end pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </div>
    
    <livewire:project-docs-modal />
</div>
