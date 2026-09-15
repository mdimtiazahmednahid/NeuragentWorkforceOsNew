<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-ড়ান্ত">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Welcome - {{ config('app.name', 'Workforce OS') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full p-6 text-center flex flex-col items-center">
            <x-app-logo-icon class="size-20 mb-6 text-indigo-600 dark:text-indigo-400" />
            <h1 class="text-4xl font-bold tracking-tight mb-2">{{ config('app.name', 'Workforce OS') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mb-8">Streamline your operations, tasks, and team management.</p>
            
            <div class="flex flex-col sm:flex-row gap-4 w-full">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-md font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                Register
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </body>
</html>
