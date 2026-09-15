@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Workforce OS')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-full shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <x-app-logo-icon class="size-full object-cover rounded-full" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Workforce OS')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-full shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <x-app-logo-icon class="size-full object-cover rounded-full" />
        </x-slot>
    </flux:brand>
@endif
