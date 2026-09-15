<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Flux\Flux;

new class extends Component {
    public array $rows = [];
    public $departments;
    public $roles;
    
    public function mount()
    {
        $this->departments = Department::all();
        $this->roles = Role::all();
        $this->addRow();
        $this->addRow();
        $this->addRow();
    }
    
    public function addRow()
    {
        $this->rows[] = [
            'name' => '',
            'username' => '',
            'email' => '',
            'phone' => '',
            'department_id' => '',
            'role_id' => '',
        ];
    }
    
    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        
        if (empty($this->rows)) {
            $this->addRow();
        }
    }
    
    public function saveAll()
    {
        $validRows = array_filter($this->rows, function ($row) {
            return !empty(trim($row['name'])) && !empty(trim($row['username'])) && !empty($row['role_id']);
        });
        
        if (empty($validRows)) {
            Flux::toast('No valid users to save. Name, Username, and Role are required.', variant: 'danger');
            return;
        }

        $savedCount = 0;
        foreach ($validRows as $row) {
            // Ensure unique username
            if (User::where('username', $row['username'])->exists()) {
                continue;
            }
            
            $role = Role::find($row['role_id']);
            
            User::create([
                'name' => $row['name'],
                'username' => $row['username'],
                'email' => !empty($row['email']) ? $row['email'] : null,
                'phone' => $row['phone'] ?? null,
                'role_id' => $row['role_id'],
                'role' => $role ? $role->name : 'USER',
                'department_id' => !empty($row['department_id']) ? $row['department_id'] : null,
                'password' => Hash::make('password123'),
                'is_default_password' => true,
                'is_active' => true,
            ]);
            $savedCount++;
        }
        
        Flux::toast("$savedCount users saved successfully.", variant: 'success');
        
        // Reset rows
        $this->rows = [];
        $this->addRow();
        $this->addRow();
        $this->addRow();
        
        // Dispatch event if parent component wants to know
        $this->dispatch('users-saved');
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-16rem)]">
    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center bg-zinc-50 dark:bg-zinc-900/50">
        <div>
            <h3 class="font-medium text-zinc-900 dark:text-white">Spreadsheet Entry</h3>
            <p class="text-xs text-zinc-500">Quickly add multiple users. Default password is 'password123'.</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="addRow" size="sm" variant="ghost" icon="plus">Add Row</flux:button>
            <flux:button wire:click="saveAll" size="sm" variant="primary" icon="document-check">Save All</flux:button>
        </div>
    </div>
    
    <div class="flex-1 overflow-auto p-0">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-zinc-100 dark:bg-zinc-800 sticky top-0 z-10 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300 w-12 text-center">#</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300">Name *</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300">Username *</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300">Email</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300">Phone</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300">Role *</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300">Department</th>
                    <th class="px-4 py-2 font-medium text-zinc-600 dark:text-zinc-300 w-12"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($rows as $index => $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <td class="px-4 py-1.5 text-center text-zinc-400 font-mono text-xs">{{ $index + 1 }}</td>
                        <td class="px-2 py-1.5">
                            <input wire:model="rows.{{ $index }}.name" type="text" class="w-full bg-transparent border-0 focus:ring-2 focus:ring-indigo-500 rounded px-2 py-1 text-sm dark:text-white" placeholder="Full Name" />
                        </td>
                        <td class="px-2 py-1.5">
                            <input wire:model="rows.{{ $index }}.username" type="text" class="w-full bg-transparent border-0 focus:ring-2 focus:ring-indigo-500 rounded px-2 py-1 text-sm dark:text-white" placeholder="Username" />
                        </td>
                        <td class="px-2 py-1.5">
                            <input wire:model="rows.{{ $index }}.email" type="email" class="w-full bg-transparent border-0 focus:ring-2 focus:ring-indigo-500 rounded px-2 py-1 text-sm dark:text-white" placeholder="Email Address" />
                        </td>
                        <td class="px-2 py-1.5">
                            <input wire:model="rows.{{ $index }}.phone" type="text" class="w-full bg-transparent border-0 focus:ring-2 focus:ring-indigo-500 rounded px-2 py-1 text-sm dark:text-white" placeholder="Phone Number" />
                        </td>
                        <td class="px-2 py-1.5">
                            <select wire:model="rows.{{ $index }}.role_id" class="w-full bg-transparent border-0 focus:ring-2 focus:ring-indigo-500 rounded px-2 py-1 text-sm dark:text-white">
                                <option value="">Select Role...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-1.5">
                            <select wire:model="rows.{{ $index }}.department_id" class="w-full bg-transparent border-0 focus:ring-2 focus:ring-indigo-500 rounded px-2 py-1 text-sm dark:text-white">
                                <option value="">Select Dept...</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-1.5 text-right">
                            <button wire:click="removeRow({{ $index }})" class="text-zinc-400 hover:text-red-500 transition-colors" tabindex="-1">
                                <flux:icon.trash class="size-4" />
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
