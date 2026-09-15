<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Salary;
use App\Models\Payslip;
use App\Models\AttendanceSession;
use Carbon\Carbon;
use Flux\Flux;

new class extends Component {
    public $users;
    public $payslips;
    
    // Salary edit modal
    public $showSalaryModal = false;
    public $editingUserId = null;
    public $salaryAmount = 0;
    public $salaryType = 'monthly';
    public $salaryCurrency = 'USD';
    
    // Run payroll modal
    public $showRunModal = false;
    public $payrollMonth = '';
    
    // Edit payslip modal
    public $showEditPayslipModal = false;
    public $editingPayslipId = null;
    public $payslipBasePay = 0;
    public $payslipBonuses = 0;
    public $payslipDeductions = 0;
    
    public function mount()
    {
        $this->payrollMonth = now()->format('Y-m');
        $this->loadData();
    }
    
    public function loadData()
    {
        $this->users = User::with('salary')->orderBy('name')->get();
        $this->payslips = Payslip::with('user')->orderBy('period_end', 'desc')->get();
    }
    
    public function editSalary($userId)
    {
        $this->editingUserId = $userId;
        $user = User::find($userId);
        $salary = $user->salary;
        
        $this->salaryAmount = $salary->amount ?? 0;
        $this->salaryType = $salary->type ?? 'monthly';
        $this->salaryCurrency = $salary->currency ?? 'USD';
        
        $this->showSalaryModal = true;
    }
    
    public function saveSalary()
    {
        $this->validate([
            'salaryAmount' => 'required|numeric|min:0',
            'salaryType' => 'required|in:monthly,hourly',
            'salaryCurrency' => 'required|string|size:3',
        ]);
        
        Salary::updateOrCreate(
            ['user_id' => $this->editingUserId],
            [
                'amount' => $this->salaryAmount,
                'type' => $this->salaryType,
                'currency' => strtoupper($this->salaryCurrency),
            ]
        );
        
        Flux::toast('Salary updated.', variant: 'success');
        $this->showSalaryModal = false;
        $this->loadData();
    }
    
    public function runPayroll()
    {
        $this->validate([
            'payrollMonth' => 'required|date_format:Y-m',
        ]);
        
        $start = Carbon::parse($this->payrollMonth . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        
        $usersWithSalary = User::with('salary')->whereHas('salary')->get();
        $generatedCount = 0;
        
        foreach ($usersWithSalary as $u) {
            // Check if payslip already exists
            $exists = Payslip::where('user_id', $u->id)
                ->where('period_start', $start->toDateString())
                ->exists();
                
            if ($exists) continue;
            
            $basePay = 0;
            $workingHours = null;
            $hourlyRate = null;
            if ($u->salary->type === 'monthly') {
                $basePay = $u->salary->amount;
            } else {
                // calculate hourly from attendance
                $sessions = AttendanceSession::with('breaks')
                    ->where('user_id', $u->id)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->get();
                    
                $totalMinutes = 0;
                foreach ($sessions as $session) {
                    if ($session->check_out_time) {
                        $mins = $session->check_in_time->diffInMinutes($session->check_out_time);
                        $breakMins = 0;
                        foreach ($session->breaks as $break) {
                            if ($break->break_end) {
                                $breakMins += $break->break_start->diffInMinutes($break->break_end);
                            }
                        }
                        $totalMinutes += ($mins - $breakMins);
                    }
                }
                $hours = $totalMinutes / 60;
                $workingHours = $hours;
                $hourlyRate = $u->salary->amount;
                $basePay = $hours * $u->salary->amount;
            }
            
            // example bonus calculation
            $bonuses = ($u->contribution_points ?? 0) * 0.5;
            
            Payslip::create([
                'user_id' => $u->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'working_hours' => $workingHours,
                'hourly_rate' => $hourlyRate,
                'base_pay' => $basePay,
                'bonuses' => $bonuses,
                'deductions' => 0,
                'net_pay' => $basePay + $bonuses,
                'status' => 'draft'
            ]);
            $generatedCount++;
        }
        
        Flux::toast("Generated {$generatedCount} payslips for " . $start->format('F Y'), variant: 'success');
        $this->showRunModal = false;
        $this->loadData();
    }
    
    public function markAsPaid($payslipId)
    {
        $ps = Payslip::find($payslipId);
        if ($ps && $ps->status === 'draft') {
            $ps->status = 'paid';
            $ps->save();
            Flux::toast('Payslip marked as paid.', variant: 'success');
            $this->loadData();
        }
    }
    
    public function editPayslip($payslipId)
    {
        if (!auth()->user()->isAdmin) {
            Flux::toast('Only Super Admins can edit payslips.', variant: 'danger');
            return;
        }
        
        $ps = Payslip::find($payslipId);
        if ($ps && $ps->status === 'draft') {
            $this->editingPayslipId = $ps->id;
            $this->payslipBasePay = $ps->base_pay;
            $this->payslipBonuses = $ps->bonuses;
            $this->payslipDeductions = $ps->deductions;
            $this->showEditPayslipModal = true;
        }
    }
    
    public function savePayslip()
    {
        if (!auth()->user()->isAdmin) return;
        
        $this->validate([
            'payslipBasePay' => 'required|numeric|min:0',
            'payslipBonuses' => 'required|numeric|min:0',
            'payslipDeductions' => 'required|numeric|min:0',
        ]);
        
        $ps = Payslip::find($this->editingPayslipId);
        if ($ps && $ps->status === 'draft') {
            $ps->base_pay = $this->payslipBasePay;
            $ps->bonuses = $this->payslipBonuses;
            $ps->deductions = $this->payslipDeductions;
            $ps->net_pay = $ps->base_pay + $ps->bonuses - $ps->deductions;
            $ps->save();
            
            Flux::toast('Payslip updated successfully.', variant: 'success');
            $this->showEditPayslipModal = false;
            $this->loadData();
        }
    }
    
    public function deletePayslip($payslipId)
    {
        if (!auth()->user()->isAdmin) {
            Flux::toast('Only Super Admins can discard payslips.', variant: 'danger');
            return;
        }
        
        $ps = Payslip::find($payslipId);
        if ($ps && $ps->status === 'draft') {
            $ps->delete();
            Flux::toast('Draft payslip discarded.', variant: 'success');
            $this->loadData();
        }
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-7xl mx-auto pb-10">
        
        <div class="flex justify-between items-end">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Payroll Manager</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Manage employee salaries and generate monthly payslips.</p>
            </div>
            <flux:button wire:click="$set('showRunModal', true)" variant="primary" icon="play">Run Payroll</flux:button>
        </div>

        <!-- Salaries Configuration Tab -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                <h3 class="font-semibold text-zinc-900 dark:text-white">Employee Salary Configurations</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-zinc-500 dark:text-zinc-400">
                    <thead class="text-xs text-zinc-700 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-semibold">Employee</th>
                            <th scope="col" class="px-6 py-3 font-semibold">Type</th>
                            <th scope="col" class="px-6 py-3 font-semibold">Base Amount</th>
                            <th scope="col" class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($users as $user)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white flex items-center gap-3">
                                <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" />
                                {{ $user->name }}
                            </td>
                            <td class="px-6 py-4">
                                @if($user->salary)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $user->salary->type === 'monthly' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' }}">
                                        {{ ucfirst($user->salary->type) }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 italic">Not set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono">
                                @if($user->salary)
                                    {{ $user->salary->currency }} {{ number_format($user->salary->amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <flux:button wire:click="editSalary({{ $user->id }})" variant="ghost" size="sm" icon="pencil">Edit</flux:button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Generated Payslips -->
        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/50">
                <h3 class="font-semibold text-zinc-900 dark:text-white">Generated Payslips</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-zinc-500 dark:text-zinc-400">
                    <thead class="text-xs text-zinc-700 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 font-semibold">Period</th>
                            <th scope="col" class="px-6 py-3 font-semibold">Employee</th>
                            <th scope="col" class="px-6 py-3 font-semibold text-right">Base Pay</th>
                            <th scope="col" class="px-6 py-3 font-semibold text-right">Net Pay</th>
                            <th scope="col" class="px-6 py-3 font-semibold text-center">Status</th>
                            <th scope="col" class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($payslips as $ps)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-white font-medium">
                                {{ \Carbon\Carbon::parse($ps->period_start)->format('M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $ps->user->name }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono">
                                {{ number_format($ps->base_pay, 2) }}
                                @if($ps->working_hours !== null)
                                    <div class="text-[10px] text-zinc-500 mt-1 leading-tight">
                                        {{ number_format($ps->working_hours, 1) }} hrs @ {{ number_format($ps->hourly_rate, 2) }}/hr
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400 font-bold">
                                {{ number_format($ps->net_pay, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($ps->status === 'paid')
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                        <div class="size-1.5 rounded-full bg-emerald-500"></div> Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                        <div class="size-1.5 rounded-full bg-amber-500 animate-pulse"></div> Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($ps->status === 'draft')
                                    @if(auth()->user()->isAdmin)
                                        <flux:dropdown position="bottom" align="end">
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" class="text-zinc-400 hover:text-indigo-600" />
                                            <flux:menu>
                                                <flux:menu.item wire:click="markAsPaid({{ $ps->id }})" icon="check-circle" class="text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 font-medium">Mark Paid</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item wire:click="editPayslip({{ $ps->id }})" icon="pencil-square">Edit Draft</flux:menu.item>
                                                <flux:menu.item wire:click="deletePayslip({{ $ps->id }})" wire:confirm="Are you sure you want to discard this draft payslip? This action cannot be undone." icon="trash" class="text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">Discard Draft</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    @else
                                        <flux:button wire:click="markAsPaid({{ $ps->id }})" variant="primary" size="sm" class="py-1">Mark Paid</flux:button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                                <flux:icon.banknotes class="size-12 mx-auto mb-3 opacity-20" />
                                No payslips generated yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Salary Modal -->
        <flux:modal wire:model="showSalaryModal" class="md:w-[400px]">
            <form wire:submit="saveSalary" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Configure Salary</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Set base compensation details.</p>
                </div>

                <div class="space-y-4">
                    <flux:select wire:model="salaryType" label="Pay Type">
                        <option value="monthly">Fixed Monthly</option>
                        <option value="hourly">Hourly Rate</option>
                    </flux:select>
                    
                    <flux:input wire:model="salaryAmount" label="Base Amount" type="number" step="0.01" />
                    
                    <flux:input wire:model="salaryCurrency" label="Currency Code" placeholder="USD" maxlength="3" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showSalaryModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </flux:modal>

        <!-- Run Payroll Modal -->
        <flux:modal wire:model="showRunModal" class="md:w-[400px]">
            <form wire:submit="runPayroll" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Run Payroll</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Generate draft payslips for a specific month.</p>
                </div>

                <flux:input wire:model="payrollMonth" label="Payroll Month" type="month" />

                <div class="bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-lg p-3 text-sm text-amber-800 dark:text-amber-400">
                    This will calculate hourly wages based on the attendance tracker and generate draft payslips for all users with configured salaries.
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showRunModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Run Payroll</flux:button>
                </div>
            </form>
        </flux:modal>
        
        <!-- Edit Payslip Modal -->
        <flux:modal wire:model="showEditPayslipModal" class="md:w-[400px]">
            <form wire:submit="savePayslip" class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Edit Draft Payslip</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Modify the amounts before marking as paid.</p>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="payslipBasePay" label="Base Pay" type="number" step="0.01" />
                    <flux:input wire:model="payslipBonuses" label="Bonuses" type="number" step="0.01" />
                    <flux:input wire:model="payslipDeductions" label="Deductions" type="number" step="0.01" />
                </div>
                
                <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 p-3 rounded-lg text-sm border border-blue-200 dark:border-blue-900/50">
                    Net Pay will be automatically recalculated: <br>
                    <strong>Net Pay = Base Pay + Bonuses - Deductions</strong>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('showEditPayslipModal', false)" variant="ghost">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </form>
        </flux:modal>
        
    </div>
</div>
