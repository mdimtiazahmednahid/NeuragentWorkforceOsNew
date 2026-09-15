<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('dashboard', 'dashboard')->name('dashboard');
    Volt::route('attendance', 'attendance')->name('attendance');
    Volt::route('projects', 'projects')->name('projects');
    Volt::route('projects/{project}/kanban', 'kanban')->name('kanban');
    Volt::route('tasks', 'tasks')->name('tasks');
    Volt::route('trashbox', 'trashbox')->name('trashbox');
    Volt::route('notifications', 'notifications')->name('notifications');
    Volt::route('users', 'users')->name('users');
    Volt::route('leaderboard', 'leaderboard')->name('leaderboard');
    Volt::route('reports', 'reports')->name('reports');
    Volt::route('analytics', 'analytics')->name('analytics');
    
    // Admin features
    Volt::route('roles', 'roles-manager')->name('roles');
    Volt::route('badges', 'badges')->name('badges');
    Volt::route('whatsapp', 'whatsapp-center')->name('whatsapp');
    Volt::route('payroll', 'payroll-manager')->name('payroll');
    Volt::route('my-payslips', 'my-payslips')->name('my-payslips');
    Route::view('profile', 'profile')->name('profile');
    Volt::route('audit', 'audit-logs')->name('audit');
    
    Volt::route('settings', 'settings')->name('settings');
});

require __DIR__.'/settings.php';
