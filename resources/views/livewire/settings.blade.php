<?php

use Livewire\Volt\Component;
use App\Models\Department;
use App\Models\Role;
use App\Models\Permission;

new class extends Component {
    public $departments;
    public $roles;
    
    // Department form
    public $newDepartmentName = '';
    public $newDepartmentCode = '';
    
    // Role form
    public $newRoleName = '';
    public $newRoleDisplayName = '';
    public $newRoleDescription = '';
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $this->departments = Department::all();
        $this->roles = Role::all();
    }
    
    public function createDepartment()
    {
        $this->validate([
            'newDepartmentName' => 'required|string|max:255|unique:departments,name',
            'newDepartmentCode' => 'nullable|string|max:50|unique:departments,code'
        ]);
        
        Department::create([
            'name' => $this->newDepartmentName,
            'code' => $this->newDepartmentCode ?: null,
            'color' => '#4f46e5', // default indigo
        ]);
        
        $this->reset(['newDepartmentName', 'newDepartmentCode']);
        $this->loadData();
    }
    
    public function createRole()
    {
        $this->validate([
            'newRoleName' => 'required|string|max:255|unique:roles,name',
            'newRoleDisplayName' => 'required|string|max:255',
        ]);
        
        Role::create([
            'name' => $this->newRoleName,
            'display_name' => $this->newRoleDisplayName,
            'description' => $this->newRoleDescription,
        ]);
        
        $this->reset(['newRoleName', 'newRoleDisplayName', 'newRoleDescription']);
        $this->loadData();
    }
    
    public function deleteDepartment($id)
    {
        Department::destroy($id);
        $this->loadData();
    }
    
    public function deleteRole($id)
    {
        Role::destroy($id);
        $this->loadData();
    }
}; ?>

<div>
    <div class="flex flex-col gap-8 w-full max-w-6xl mx-auto">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Administration</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Manage departments, roles, and global configuration.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Departments -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Departments</h2>
                
                <form wire:submit="createDepartment" class="flex gap-2 mb-6">
                    <div class="flex-[2]">
                        <flux:input wire:model="newDepartmentName" placeholder="New Department Name" required />
                    </div>
                    <div class="flex-1">
                        <flux:input wire:model="newDepartmentCode" placeholder="Code (e.g. 011501)" />
                    </div>
                    <flux:button type="submit" variant="primary">Add</flux:button>
                </form>
                
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border-t border-zinc-100 dark:border-zinc-800">
                    @forelse($departments as $dept)
                        <div class="py-3 flex justify-between items-center group">
                            <div>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $dept->name }}</span>
                                @if($dept->code)
                                    <span class="ml-2 text-xs text-zinc-500 font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-0.5 rounded">{{ $dept->code }}</span>
                                @endif
                            </div>
                            <flux:button wire:click="deleteDepartment({{ $dept->id }})" wire:confirm="Are you sure you want to delete this department?" variant="ghost" size="sm" icon="trash" class="text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                    @empty
                        <div class="py-4 text-center text-zinc-500">No departments configured.</div>
                    @endforelse
                </div>
            </div>

            <!-- Roles -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Roles</h2>
                
                <form wire:submit="createRole" class="space-y-3 mb-6">
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input wire:model="newRoleDisplayName" placeholder="Display Name (e.g. Manager)" required />
                        <flux:input wire:model="newRoleName" placeholder="Key (e.g. manager)" required />
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <flux:input wire:model="newRoleDescription" placeholder="Description..." />
                        </div>
                        <flux:button type="submit" variant="primary">Add</flux:button>
                    </div>
                </form>
                
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border-t border-zinc-100 dark:border-zinc-800">
                    @forelse($roles as $role)
                        <div class="py-3 flex justify-between items-center group">
                            <div>
                                <div class="font-medium text-zinc-700 dark:text-zinc-300">{{ $role->display_name }}</div>
                                <div class="text-xs text-zinc-500 font-mono">{{ $role->name }}</div>
                            </div>
                            @if($role->name !== 'admin')
                                <flux:button wire:click="deleteRole({{ $role->id }})" wire:confirm="Are you sure?" variant="ghost" size="sm" icon="trash" class="text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                            @endif
                        </div>
                    @empty
                        <div class="py-4 text-center text-zinc-500">No roles configured.</div>
                    @endforelse
                </div>
            </div>
            
            <!-- Global Config -->
            <div class="col-span-full bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Global Settings</h2>
                <div class="space-y-6">
                    <flux:switch checked="{{ config('services.whatsapp.enabled') }}" disabled label="WhatsApp Integration" description="Enable or disable outgoing WhatsApp notifications." />
                    <flux:switch checked disabled label="Audit Logging" description="Track all critical user actions in the system." />
                    <flux:switch checked disabled label="Leaderboard" description="Enable contribution points and badges for employees." />
                </div>
                <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-800 flex justify-end">
                    <flux:button variant="primary" disabled>Save Settings</flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
