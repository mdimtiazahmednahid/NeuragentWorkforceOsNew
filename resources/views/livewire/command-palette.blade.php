<?php

use Livewire\Volt\Component;

new class extends Component {
    public $search = '';
    
    public function getLinksProperty()
    {
        $links = [
            ['name' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'home'],
            ['name' => 'Tasks', 'url' => route('tasks'), 'icon' => 'clipboard-document-check'],
            ['name' => 'Projects', 'url' => route('projects'), 'icon' => 'folder'],
            ['name' => 'Attendance', 'url' => route('attendance'), 'icon' => 'clock'],
            ['name' => 'Leaderboard', 'url' => route('leaderboard'), 'icon' => 'trophy'],
        ];
        
        if (auth()->user() && auth()->user()->hasPermission('full_control')) {
            $links = array_merge($links, [
                ['name' => 'Users & Spreadsheets', 'url' => route('users'), 'icon' => 'users'],
                ['name' => 'Payroll Manager', 'url' => route('payroll'), 'icon' => 'banknotes'],
                ['name' => 'Roles & Privileges', 'url' => route('roles'), 'icon' => 'shield-check'],
                ['name' => 'Badges & Gamification', 'url' => route('badges'), 'icon' => 'gift'],
                ['name' => 'WhatsApp Center', 'url' => route('whatsapp'), 'icon' => 'chat-bubble-left-ellipsis'],
            ]);
        }
        
        if (!empty($this->search)) {
            return collect($links)->filter(function($link) {
                return str_contains(strtolower($link['name']), strtolower($this->search));
            })->values()->toArray();
        }
        
        return $links;
    }
}; ?>

<div x-data="{ 
    init() {
        // Listen for Cmd+K or Ctrl+K globally
        window.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                $dispatch('modal-show', { name: 'command-palette' });
            }
        });
    }
}">
    <!-- Trigger Button -->
    <button x-on:click="$dispatch('modal-show', { name: 'command-palette' })" class="flex items-center gap-2 px-3 py-1.5 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-md transition-colors w-full lg:w-64 border border-transparent dark:border-zinc-700">
        <flux:icon.magnifying-glass class="size-4" />
        <span class="flex-1 text-left">Spotlight Search...</span>
        <span class="text-xs font-medium text-zinc-400 bg-zinc-200 dark:bg-zinc-900 rounded px-1.5 py-0.5">⌘K</span>
    </button>

    <!-- Modal -->
    <flux:modal name="command-palette" class="md:w-[600px] p-0 overflow-hidden bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl border border-zinc-200/50 dark:border-zinc-700/50 shadow-2xl">
        <div class="flex flex-col h-full max-h-[80vh]">
            <!-- Search Input -->
            <div class="p-4 border-b border-zinc-200/50 dark:border-zinc-800/50 flex items-center gap-3 bg-white/50 dark:bg-zinc-900/50">
                <flux:icon.magnifying-glass class="size-6 text-zinc-400" />
                <input wire:model.live.debounce.150ms="search" type="text" placeholder="Search pages or type a command..." class="w-full bg-transparent border-none focus:ring-0 text-xl text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 p-0" autofocus>
                <div class="text-xs font-mono text-zinc-400 bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">ESC</div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-2">
                @if(count($this->links) > 0)
                    <div class="px-3 py-2 text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Navigation</div>
                    <ul class="space-y-1 mb-4">
                        @foreach($this->links as $link)
                            <li>
                                <a href="{{ $link['url'] }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 text-zinc-700 dark:text-zinc-300 transition-colors group">
                                    <flux:icon dynamic :name="$link['icon']" class="size-5 text-zinc-400 group-hover:text-indigo-500" />
                                    <span class="font-medium">{{ $link['name'] }}</span>
                                    <span class="ml-auto text-xs text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">Jump to &rarr;</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="py-12 text-center text-zinc-500">
                        <flux:icon.magnifying-glass class="size-8 mx-auto mb-3 opacity-20" />
                        No results found for "{{ $search }}"
                    </div>
                @endif
                
                @if(empty($this->search))
                    <div class="px-3 py-2 text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider border-t border-zinc-100 dark:border-zinc-800 pt-4 mt-2">Keyboard Shortcuts</div>
                    <ul class="space-y-1 pb-2">
                        <li class="flex items-center justify-between px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>Open Spotlight Search</span>
                            <span class="text-xs font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded border border-zinc-200 dark:border-zinc-700 shadow-sm text-zinc-500">⌘ + K</span>
                        </li>
                        <li class="flex items-center justify-between px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>Close Modal</span>
                            <span class="text-xs font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded border border-zinc-200 dark:border-zinc-700 shadow-sm text-zinc-500">ESC</span>
                        </li>
                        <li class="flex items-center justify-between px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>New Task Quick Add</span>
                            <span class="text-xs font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded border border-zinc-200 dark:border-zinc-700 shadow-sm text-zinc-500">T</span>
                        </li>
                        <li class="flex items-center justify-between px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>Clock In / Out</span>
                            <span class="text-xs font-mono bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded border border-zinc-200 dark:border-zinc-700 shadow-sm text-zinc-500">C</span>
                        </li>
                    </ul>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
