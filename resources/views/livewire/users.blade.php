<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

new class extends Component {
    public $users;
    public $departments;
    public $roles;
    public $view = 'list';
    
    protected $queryString = ['view'];
    
    // Modal state
    public $showCreateModal = false;
    public $paymentDetailsUser = null;
    
    // Form fields
    public $name = '';
    public $username = '';
    public $email = '';
    public $phone = '';
    public $role_id = '';
    public $department_id = '';
    public $send_credentials_whatsapp = false;
    
    // Edit Modal state
    public $editUserId = null;
    public $editName = '';
    public $editUsername = '';
    public $editEmail = '';
    public $editPhone = '';
    public $editRoleId = '';
    public $editDepartmentId = '';
    public $editIsActive = true;
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $this->departments = Department::all();
        $this->roles = Role::all();
        $this->users = User::with(['department', 'roleModel'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function saveUser()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
        ]);
        
        $role = Role::find($this->role_id);
        
        $user = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'role_id' => $this->role_id,
            'role' => $role->name, // Keep legacy role field in sync
            'department_id' => $this->department_id,
            'password' => Hash::make('password123'), // Default password
            'is_default_password' => true,
        ]);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "Created user {$user->name}",
            'ip_address' => request()->ip(),
        ]);
        
        $this->reset(['name', 'username', 'email', 'phone', 'role_id', 'department_id', 'showCreateModal', 'send_credentials_whatsapp']);
        $this->loadData();
    }
    
    public function setView($view)
    {
        $this->view = $view;
    }
    
    #[\Livewire\Attributes\On('users-saved')]
    public function refreshUsers()
    {
        $this->loadData();
        $this->view = 'list';
    }
    
    public function resetPassword($userId)
    {
        if (!auth()->user()->isAdmin) {
            return;
        }
        
        $user = User::find($userId);
        if ($user) {
            $user->update([
                'password' => Hash::make('password123'),
                'is_default_password' => true,
                'must_change_password' => true
            ]);
            
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'reset_password',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => "Reset password for {$user->name}",
                'ip_address' => request()->ip(),
            ]);
            
            \Flux\Flux::toast(variant: 'success', text: "Password reset to 'password123'");
        }
    }
    
    public function deleteUser($userId)
    {
        if (!auth()->user()->isAdmin) {
            \Flux\Flux::toast(variant: 'danger', text: 'You do not have permission to delete users.');
            return;
        }
        
        $user = User::find($userId);
        if ($user) {
            if ($user->id === auth()->id()) {
                \Flux\Flux::toast(variant: 'danger', text: 'You cannot delete yourself.');
                return;
            }
            
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'model_type' => User::class,
                'model_id' => $user->id,
                'description' => "Deleted user {$user->name}",
                'ip_address' => request()->ip(),
            ]);
            
            $user->delete();
            \Flux\Flux::toast(variant: 'success', text: "User deleted successfully");
            $this->loadData();
        }
    }
    
    public function viewPaymentDetails($userId)
    {
        $this->paymentDetailsUser = User::find($userId);
        \Flux\Flux::modal('payment-details')->show();
    }
    
    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->editUserId = $user->id;
        $this->editName = $user->name;
        $this->editUsername = $user->username;
        $this->editEmail = $user->email;
        $this->editPhone = $user->phone;
        $this->editRoleId = $user->role_id;
        $this->editDepartmentId = $user->department_id;
        $this->editIsActive = $user->is_active;
        
        \Flux\Flux::modal('edit-user')->show();
    }

    public function updateUser()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editUsername' => 'required|string|max:255|unique:users,username,' . $this->editUserId,
            'editEmail' => 'nullable|email|unique:users,email,' . $this->editUserId,
            'editPhone' => 'nullable|string|max:20',
            'editRoleId' => 'required|exists:roles,id',
            'editDepartmentId' => 'required|exists:departments,id',
            'editIsActive' => 'boolean',
        ]);
        
        $user = User::findOrFail($this->editUserId);
        $role = Role::find($this->editRoleId);
        
        $user->update([
            'name' => $this->editName,
            'username' => $this->editUsername,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
            'role_id' => $this->editRoleId,
            'role' => $role->name,
            'department_id' => $this->editDepartmentId,
            'is_active' => $this->editIsActive,
        ]);
        
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "Updated user {$user->name}",
            'ip_address' => request()->ip(),
        ]);
        
        \Flux\Flux::modal('edit-user')->close();
        \Flux\Flux::toast(variant: 'success', text: "User updated successfully");
        $this->loadData();
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Users</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Manage team members and their roles.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="flex bg-zinc-100 dark:bg-zinc-800/50 p-1 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <button wire:click="setView('list')" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $view === 'list' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">List</button>
                    <button wire:click="setView('spreadsheet')" class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors {{ $view === 'spreadsheet' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">Spreadsheet</button>
                </div>
                
                @if($view === 'list')
                    <flux:modal.trigger name="create-user">
                        <flux:button variant="primary" icon="plus">New User</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>

        @if($view === 'list')

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-4 font-medium">Name</th>
                            <th class="px-6 py-4 font-medium">Role</th>
                            <th class="px-6 py-4 font-medium">Department</th>
                            <th class="px-6 py-4 font-medium">Status</th>
                            <th class="px-6 py-4 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($users as $user)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($user->avatar)
                                            <div class="size-8 rounded-full overflow-hidden shrink-0 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                                                <img src="{{ Storage::url($user->avatar) }}" class="size-full object-cover" />
                                            </div>
                                        @else
                                            <flux:avatar size="sm" :name="$user->name" />
                                        @endif
                                        <div>
                                            <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-zinc-500 text-xs">{{ $user->email ?? $user->phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                                        {{ $user->roleModel?->display_name ?? $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                    {{ $user->department?->name ?? 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        <span class="text-zinc-700 dark:text-zinc-300">{{ $user->is_active ? 'Active' : 'Suspended' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="text-zinc-400 hover:text-indigo-600" />
                                        <flux:menu>
                                            <flux:menu.item wire:click="editUser({{ $user->id }})" icon="pencil-square">Edit</flux:menu.item>
                                            <flux:menu.item wire:click="viewPaymentDetails({{ $user->id }})" icon="banknotes">Payment Details</flux:menu.item>
                                            @if(auth()->user()->isAdmin)
                                                <flux:menu.separator />
                                                <flux:menu.item wire:click="resetPassword({{ $user->id }})" wire:confirm="Are you sure you want to reset this user's password to password123?" icon="key">Reset Password</flux:menu.item>
                                                <flux:menu.item wire:click="deleteUser({{ $user->id }})" wire:confirm="Are you sure you want to completely delete this user? This action cannot be undone." icon="trash" class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">Delete User</flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @elseif($view === 'spreadsheet')
            <livewire:spreadsheet-users />
        @endif

        <flux:modal name="create-user" class="md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Add Team Member</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Invite a new user to Workforce OS.</p>
                </div>

                <form wire:submit="saveUser" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="name" label="Full Name" placeholder="e.g. John Doe" />
                        <flux:input wire:model="username" label="Username" placeholder="e.g. jdoe" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="email" type="email" label="Email (Optional)" placeholder="john@example.com" />
                        <flux:input wire:model="phone" label="Phone Number" placeholder="+1234567890" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="department_id" label="Department">
                            <option value="">Select department...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:select wire:model="role_id" label="Role">
                            <option value="">Select role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 p-3 rounded-lg text-sm border border-blue-200 dark:border-blue-900/50 mt-2">
                        <flux:icon.information-circle class="size-4 inline-block mr-1 -mt-0.5" />
                        The user's default password will be <strong>password123</strong>. They will be prompted to change it upon first login.
                    </div>
                    
                    <flux:checkbox wire:model="send_credentials_whatsapp" label="Send credentials via WhatsApp" />
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Add User</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
        
        <flux:modal name="edit-user" class="md:w-[500px]">
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit Team Member</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Update user details and access.</p>
                </div>

                <form wire:submit="updateUser" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="editName" label="Full Name" placeholder="e.g. John Doe" />
                        <flux:input wire:model="editUsername" label="Username" placeholder="e.g. jdoe" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="editEmail" type="email" label="Email (Optional)" placeholder="john@example.com" />
                        <flux:input wire:model="editPhone" label="Phone Number" placeholder="+1234567890" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="editDepartmentId" label="Department">
                            <option value="">Select department...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </flux:select>
                        
                        <flux:select wire:model="editRoleId" label="Role">
                            <option value="">Select role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    
                    <div class="pt-2">
                        <flux:checkbox wire:model="editIsActive" label="Account Active" description="If unchecked, the user will not be able to log in." />
                    </div>
                    
                    <div class="flex justify-end gap-2 pt-4">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Save Changes</flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
        
        <flux:modal name="payment-details" class="md:w-[500px]">
            @if($paymentDetailsUser)
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Payment Details for {{ $paymentDetailsUser->name }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Use this information for payroll processing.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Bank Account Details</span>
                            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm">
                                @if($paymentDetailsUser->bank_account_details)
                                    <div class="grid grid-cols-2 gap-y-4 gap-x-4">
                                        <div>
                                            <div class="text-xs text-zinc-500 mb-0.5">Bank Name</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->bank_account_details['name'] ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-zinc-500 mb-0.5">Account Name</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->bank_account_details['account_name'] ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-zinc-500 mb-0.5">Account Number</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->bank_account_details['account_number'] ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-zinc-500 mb-0.5">Routing / Branch</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->bank_account_details['routing'] ?? '-' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-zinc-500 text-sm">Not provided.</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <span class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Mobile Banking Details</span>
                            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm">
                                @if($paymentDetailsUser->mobile_banking_details)
                                    <div class="grid grid-cols-2 gap-y-4 gap-x-4">
                                        <div>
                                            <div class="text-xs text-zinc-500 mb-0.5">Provider</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->mobile_banking_details['name'] ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-zinc-500 mb-0.5">Account Type</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->mobile_banking_details['type'] ?? '-' }}</div>
                                        </div>
                                        <div class="col-span-2">
                                            <div class="text-xs text-zinc-500 mb-0.5">Mobile Number</div>
                                            <div class="font-medium text-zinc-900 dark:text-zinc-200">{{ $paymentDetailsUser->mobile_banking_details['number'] ?? '-' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-zinc-500 text-sm">Not provided.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4">
                        <flux:modal.close>
                            <flux:button variant="primary">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            @endif
        </flux:modal>
    </div>
</div>
