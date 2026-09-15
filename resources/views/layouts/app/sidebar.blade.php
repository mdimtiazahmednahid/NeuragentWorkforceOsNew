<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder" :href="route('projects')" :current="request()->routeIs('projects') || request()->routeIs('kanban')" wire:navigate>
                        {{ __('Projects') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('tasks')" :current="request()->routeIs('tasks') && request()->query('view') !== 'calendar'" wire:navigate>
                        {{ __('Tasks') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item icon="calendar" :href="route('tasks', ['view' => 'calendar'])" :current="request()->routeIs('tasks') && request()->query('view') === 'calendar'" wire:navigate>
                        {{ __('Calendar') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('attendance')" :current="request()->routeIs('attendance')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-currency-dollar" :href="route('my-payslips')" :current="request()->routeIs('my-payslips')" wire:navigate>
                        {{ __('My Payslips') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="trophy" :href="route('leaderboard')" :current="request()->routeIs('leaderboard')" wire:navigate>
                        {{ __('Leaderboard') }}
                    </flux:sidebar.item>
                    
                    @if(auth()->user()->isAdmin)
                        <flux:sidebar.item icon="chart-bar-square" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                            {{ __('Reports') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="presentation-chart-line" :href="route('analytics')" :current="request()->routeIs('analytics')" wire:navigate>
                            {{ __('Analytics') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('users')" :current="request()->routeIs('users')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav variant="bottom">
                
                    @if(auth()->user()->isAdmin)
                        <flux:sidebar.item icon="gift" :href="route('badges')" :current="request()->routeIs('badges')" wire:navigate>
                            {{ __('Badges & Awards') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="banknotes" :href="route('payroll')" :current="request()->routeIs('payroll')" wire:navigate>
                            {{ __('Payroll Manager') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('audit')" :current="request()->routeIs('audit')" wire:navigate>
                            {{ __('Audit Logs') }}
                        </flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('roles')" :current="request()->routeIs('roles')" wire:navigate>
                        {{ __('Roles & Privileges') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="trash" :href="route('trashbox')" :current="request()->routeIs('trashbox')" wire:navigate>
                        {{ __('Trashbox') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('settings')" :current="request()->routeIs('settings')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.nav>

        </flux:sidebar>

        <!-- Global Header -->
        <flux:header class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 items-center justify-between">
            <div class="flex items-center gap-4 w-full">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                <!-- Left: Logo (Mobile) / Global Search -->
                <div class="flex items-center gap-4 flex-1">
                    <div class="lg:hidden">
                        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
                    </div>
                    <div class="hidden lg:block w-64">
                        <livewire:command-palette />
                    </div>
                </div>

                <!-- Center: Quick Actions -->
                <div class="hidden md:flex items-center justify-center gap-3 flex-1">
                    <livewire:user-quick-stats />
                    <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-700 mx-1 hidden lg:block"></div>
                    <flux:button href="{{ route('tasks') }}" wire:navigate size="sm" variant="ghost" icon="plus" class="text-zinc-600 dark:text-zinc-300 hidden xl:flex">Task</flux:button>
                    <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-700 mx-1"></div>
                    <livewire:quick-timer />
                </div>

                <!-- Right: User Switcher / Profile -->
                <div class="flex items-center justify-end gap-3 flex-1">
                    <livewire:chat-indicator />

                    <livewire:notification-indicator />
                    
                    <livewire:account-switcher />

                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" class="!p-0 !h-8 !w-8 !min-h-8 !min-w-8 !rounded-full border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-none">
                            <img src="{{ auth()->user()->avatar ? Storage::url(auth()->user()->avatar) : '' }}" class="size-full object-cover {{ !auth()->user()->avatar ? 'hidden' : '' }}" />
                            <flux:avatar :initials="auth()->user()->initials()" class="size-full {{ auth()->user()->avatar ? 'hidden' : '' }}" />
                        </flux:button>

                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <div class="size-8 rounded-full overflow-hidden shrink-0">
                                            <img src="{{ auth()->user()->avatar ? Storage::url(auth()->user()->avatar) : '' }}" class="size-full object-cover {{ !auth()->user()->avatar ? 'hidden' : '' }}" />
                                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" class="size-full {{ auth()->user()->avatar ? 'hidden' : '' }}" />
                                        </div>

                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                    {{ __('Settings') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer"
                                    data-test="logout-button"
                                >
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <livewire:global-chat />

        @fluxScripts
    </body>
</html>
