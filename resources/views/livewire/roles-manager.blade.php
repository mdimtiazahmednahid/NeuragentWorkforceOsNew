<?php

use Livewire\Volt\Component;
use App\Models\Role;
use Flux\Flux;

new class extends Component {
    public $roles;
    
    // Modal state for metadata editing
    public $showCreateModal = false;
    
    // Form fields for create/edit metadata
    public $roleId = null;
    public $name = '';
    public $display_name = '';
    public $hierarchy_level = 100;
    public $is_system = false;
    public $clone_from = '';
    
    // Matrix state for inline editing
    // Map of roleId => [permission1, permission2, ...]
    public $rolePermissions = [];
    public $newRolePermissions = [];
    
    // Grouped legacy permissions
    public $groupedPermissions = [
        'Global & System' => [
            'full_control', 'dashboard.view', 'settings.manage', 'audit.view', 'queues.manage', 'logs.view'
        ],
        'Users & Roles' => [
            'users.manage', 'users.department.manage', 'users.sessions.manage', 'roles.manage', 'permissions.manage', 'departments.manage', 'profile.edit'
        ],
        'Attendance' => [
            'attendance.self', 'attendance.team_summary', 'attendance.department.view', 'attendance.department.manage', 'attendance.global.manage'
        ],
        'Tasks' => [
            'tasks.own.view', 'tasks.own.work', 'tasks.comment', 'tasks.team.assign', 'tasks.team.view', 'tasks.team.update', 'tasks.department.assign', 'tasks.department.view', 'tasks.department.manage', 'tasks.review', 'tasks.approve', 'tasks.global.manage'
        ],
        'Projects' => [
            'projects.view', 'projects.client_view', 'projects.department.manage', 'projects.global.manage', 'deliverables.view'
        ],
        'Reports & Analytics' => [
            'reports.view', 'reports.team.view', 'reports.department.export', 'reports.global.export',
            'analytics.own.view', 'analytics.view_limited', 'analytics.team.view', 'analytics.department.view', 'analytics.global.view'
        ],
        'Gamification & Misc' => [
            'badges.manage', 'contributions.manage', 'labels.manage', 'notifications.manage', 'notifications.in_app.view', 'notifications.department.send'
        ],
        'Escalations' => [
            'blockers.escalate', 'blockers.resolve'
        ]
    ];
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $this->roles = Role::withCount('users')->orderBy('hierarchy_level', 'asc')->get();
        
        // Populate the inline permissions state
        foreach ($this->roles as $role) {
            $this->rolePermissions[$role->id] = $role->permissions ?? [];
        }
    }
    
    public function savePermissions($id)
    {
        $role = Role::find($id);
        if (!$role) return;
        
        $role->permissions = $this->rolePermissions[$id] ?? [];
        $role->save();
        
        Flux::toast("Permissions saved for {$role->display_name}", variant: 'success');
        
        // Log the change
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated_permissions',
            'model_type' => Role::class,
            'model_id' => $role->id,
            'description' => "Updated permissions for role {$role->name}",
            'ip_address' => request()->ip(),
        ]);
    }
    
    public function createRole()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }
    
    public function editRole($id)
    {
        $role = Role::find($id);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->display_name = $role->display_name;
        $this->hierarchy_level = $role->hierarchy_level ?? 100;
        $this->is_system = $role->is_system;
        $this->clone_from = '';
        
        $this->showCreateModal = true;
    }
    
    public function saveRoleMetadata()
    {
        $this->validate([
            'display_name' => 'required|string|max:255',
        ]);
        
        if (empty($this->name)) {
            $this->name = strtoupper(str_replace(' ', '_', $this->display_name));
        }
        
        if ($this->roleId) {
            $role = Role::find($this->roleId);
            if (!$role->is_system) {
                $role->name = $this->name;
                $role->display_name = $this->display_name;
                $role->hierarchy_level = $this->hierarchy_level;
                $role->save();
            }
            Flux::toast('Role metadata updated.', variant: 'success');
        } else {
            $this->validate([
                'name' => 'required|unique:roles,name',
            ]);
            
            // Handle clone
            $permissionsToClone = [];
            if (!empty($this->clone_from)) {
                $cloneRole = Role::find($this->clone_from);
                if ($cloneRole) {
                    $permissionsToClone = $cloneRole->permissions ?? [];
                }
            }
            
            Role::create([
                'name' => $this->name,
                'display_name' => $this->display_name,
                'hierarchy_level' => (int) $this->hierarchy_level,
                'is_system' => false,
                'permissions' => !empty($this->clone_from) ? $permissionsToClone : $this->newRolePermissions,
            ]);
            
            Flux::toast('Role created successfully.', variant: 'success');
        }
        
        $this->showCreateModal = false;
        $this->loadData();
    }
    
    public function deleteRole($id)
    {
        $role = Role::withCount('users')->find($id);
        
        if ($role->is_system) {
            Flux::toast('Cannot delete a system role.', variant: 'danger');
            return;
        }
        
        if ($role->users_count > 0) {
            Flux::toast('Cannot delete role because it is assigned to users. Reassign them first.', variant: 'danger');
            return;
        }
        
        $role->delete();
        Flux::toast('Role deleted successfully.', variant: 'success');
        $this->loadData();
    }
    
    public function resetForm()
    {
        $this->roleId = null;
        $this->name = '';
        $this->display_name = '';
        $this->hierarchy_level = 100;
        $this->is_system = false;
        $this->clone_from = '';
        $this->newRolePermissions = [];
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-[1400px] mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Role & Permission Builder</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Define custom roles and manage their access privileges dynamically.</p>
            </div>
            
            <flux:button wire:click="createRole" variant="primary" icon="plus">New Role</flux:button>
        </div>

        <div class="flex flex-col gap-8">
            @foreach($roles as $role)
                <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden" wire:key="role-{{ $role->id }}">
                    <!-- Header -->
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <h3 class="font-semibold text-lg text-zinc-900 dark:text-white">{{ $role->display_name }}</h3>
                                @if($role->is_system)
                                    <flux:badge size="sm" color="indigo">System</flux:badge>
                                @endif
                                <span class="text-sm font-mono text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-2 rounded">{{ $role->name }}</span>
                                <span class="text-xs text-zinc-500 border border-zinc-200 dark:border-zinc-700 px-2 py-0.5 rounded-full">Level {{ $role->hierarchy_level }}</span>
                            </div>
                            <p class="text-sm text-zinc-500 mt-1">{{ $role->users_count }} active user(s) assigned to this role.</p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <flux:button wire:click="savePermissions({{ $role->id }})" variant="primary" size="sm" icon="check">Save Permissions</flux:button>
                            
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="text-zinc-400" />
                                <flux:menu>
                                    <flux:menu.item wire:click="editRole({{ $role->id }})" icon="pencil-square">Edit Role Metadata</flux:menu.item>
                                    @if(!$role->is_system)
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="deleteRole({{ $role->id }})" wire:confirm="Are you sure you want to delete this role?" icon="trash" class="text-red-600">Delete Role</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                    
                    <!-- Permissions Grid -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-10">
                            @foreach($groupedPermissions as $groupName => $perms)
                                <div>
                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4 border-b border-zinc-200 dark:border-zinc-800 pb-2">
                                        {{ $groupName }}
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach($perms as $perm)
                                            <flux:checkbox 
                                                wire:model="rolePermissions.{{ $role->id }}" 
                                                value="{{ $perm }}" 
                                                label="{{ $perm }}" 
                                                class="!items-start"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Metadata Modal (Create / Rename) -->
        <flux:modal wire:model="showCreateModal" class="md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $roleId ? 'Edit Role Metadata' : 'Create Custom Role' }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Configure core attributes for this role.</p>
                </div>
                
                @if(!$roleId)
                <div class="bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 text-sm text-zinc-600 dark:text-zinc-400 flex items-start gap-2">
                    <flux:icon.information-circle class="size-5 shrink-0 mt-0.5" />
                    <p>Select privileges below, or clone them from an existing role. You can also modify them later from the main grid view.</p>
                </div>
                @endif

                <form wire:submit="saveRoleMetadata" class="space-y-6">
                    <flux:input wire:model="display_name" label="Role Name" placeholder="e.g. HR Manager" :disabled="$is_system" />
                    <flux:input wire:model="name" label="System Name (Optional)" placeholder="e.g. HR_MANAGER" :disabled="$is_system" hint="Leave blank to auto-generate" />
                    <flux:input wire:model="hierarchy_level" type="number" label="Hierarchy Level" placeholder="100" :disabled="$is_system" />
                    
                    @if(!$roleId)
                    <flux:select wire:model="clone_from" label="Clone Privileges From (Overrides selections below)">
                        <option value="">Do not clone...</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->display_name }}</option>
                        @endforeach
                    </flux:select>
                    
                    <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Select Privileges</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6 max-h-[300px] overflow-y-auto pr-2 rounded-lg border border-zinc-200 dark:border-zinc-800 p-4 bg-zinc-50/50 dark:bg-zinc-900/50">
                            @foreach($groupedPermissions as $groupName => $perms)
                                <div>
                                    <h4 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-2 uppercase tracking-wider">{{ $groupName }}</h4>
                                    <div class="space-y-2">
                                        @foreach($perms as $perm)
                                            <flux:checkbox 
                                                wire:model="newRolePermissions" 
                                                value="{{ $perm }}" 
                                                label="{{ $perm }}" 
                                                class="!items-start"
                                            />
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if($is_system)
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-300 p-3 rounded-lg text-sm border border-indigo-200 dark:border-indigo-900/50 mt-2">
                            <flux:icon.information-circle class="size-4 inline-block mr-1 -mt-0.5" />
                            This is a system role. You cannot rename or alter its hierarchy.
                        </div>
                    @endif
                    
                    <div class="flex justify-end gap-2 pt-2">
                        <flux:button wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                        <flux:button type="submit" variant="primary">Save Configuration</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    </div>
</div>
