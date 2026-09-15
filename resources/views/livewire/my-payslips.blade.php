<?php

use Livewire\Volt\Component;
use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $payslips;
    public $selectedPayslip = null;
    public $showDetailModal = false;
    
    public function mount()
    {
        $this->payslips = Payslip::where('user_id', Auth::id())->orderBy('period_end', 'desc')->get();
    }
    
    public function viewDetails($id)
    {
        $this->selectedPayslip = Payslip::where('id', $id)->where('user_id', Auth::id())->first();
        if ($this->selectedPayslip) {
            $this->showDetailModal = true;
        }
    }
}; ?>

<div>
    <div class="flex flex-col gap-6 w-full max-w-5xl mx-auto pb-10">
        
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">My Payslips</h1>
            <p class="text-zinc-500 dark:text-zinc-400">View and download your monthly salary statements.</p>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-zinc-500 dark:text-zinc-400">
                    <thead class="text-xs text-zinc-700 uppercase bg-zinc-50 dark:bg-zinc-800/50 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Period</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Base Pay</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Bonuses</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Net Pay</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-center">Status</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($payslips as $ps)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors cursor-pointer" wire:click="viewDetails({{ $ps->id }})">
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-white font-medium flex items-center gap-3">
                                <div class="bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400 p-2 rounded-lg">
                                    <flux:icon.calendar-days class="size-5" />
                                </div>
                                <div>
                                    <p>{{ \Carbon\Carbon::parse($ps->period_start)->format('F Y') }}</p>
                                    <p class="text-xs text-zinc-400 font-normal">
                                        {{ \Carbon\Carbon::parse($ps->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($ps->period_end)->format('M d, Y') }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono">
                                {{ number_format($ps->base_pay, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-emerald-600 dark:text-emerald-400">
                                +{{ number_format($ps->bonuses, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-zinc-900 dark:text-white font-bold text-base">
                                {{ number_format($ps->net_pay, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($ps->status === 'paid')
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                        <div class="size-1.5 rounded-full bg-emerald-500"></div> Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-md text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                        <div class="size-1.5 rounded-full bg-amber-500 animate-pulse"></div> Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <flux:button variant="ghost" size="sm" icon="eye">View</flux:button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-zinc-500">
                                <flux:icon.document-currency-dollar class="size-12 mx-auto mb-3 opacity-20" />
                                <p>No payslips found for your account.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Detailed Payslip Modal -->
        <flux:modal wire:model="showDetailModal" class="md:w-[500px]">
            @if($selectedPayslip)
            <div class="p-2">
                <div class="text-center mb-6 border-b border-zinc-200 dark:border-zinc-800 pb-6">
                    <div class="inline-flex items-center justify-center size-12 bg-indigo-600 text-white rounded-xl mb-4 shadow-lg shadow-indigo-500/30">
                        <flux:icon.banknotes class="size-6" />
                    </div>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-1">Salary Statement</h2>
                    <p class="text-zinc-500 dark:text-zinc-400 uppercase tracking-widest text-xs font-semibold">
                        {{ \Carbon\Carbon::parse($selectedPayslip->period_start)->format('F Y') }}
                    </p>
                </div>
                
                <div class="space-y-4 mb-8">
                    <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                        <span class="text-zinc-500 dark:text-zinc-400">Employee</span>
                        <span class="font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                        <span class="text-zinc-500 dark:text-zinc-400">Base Salary / Hourly Pay</span>
                        <span class="font-mono text-zinc-900 dark:text-white">{{ number_format($selectedPayslip->base_pay, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                        <span class="text-zinc-500 dark:text-zinc-400">Bonuses (Contribution Points)</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400">+ {{ number_format($selectedPayslip->bonuses, 2) }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                        <span class="text-zinc-500 dark:text-zinc-400">Deductions</span>
                        <span class="font-mono text-red-500">- {{ number_format($selectedPayslip->deductions, 2) }}</span>
                    </div>
                </div>
                
                <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl p-5 flex justify-between items-center border border-zinc-200 dark:border-zinc-700">
                    <span class="text-lg font-semibold text-zinc-700 dark:text-zinc-300">Total Net Pay</span>
                    <span class="text-3xl font-bold font-mono text-indigo-600 dark:text-indigo-400">
                        <span class="text-sm text-zinc-400 font-sans mr-1">{{ auth()->user()->salary->currency ?? 'USD' }}</span>{{ number_format($selectedPayslip->net_pay, 2) }}
                    </span>
                </div>
                
                <div class="flex justify-end gap-2 mt-8">
                    <flux:button wire:click="$set('showDetailModal', false)" variant="ghost">Close</flux:button>
                    <!-- In a real app, this would trigger a PDF generation route -->
                    <flux:button icon="arrow-down-tray" class="cursor-not-allowed opacity-50">Download PDF</flux:button>
                </div>
            </div>
            @endif
        </flux:modal>
        
    </div>
</div>
