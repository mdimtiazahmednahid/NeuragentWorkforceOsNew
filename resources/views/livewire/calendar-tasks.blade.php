<?php

use Livewire\Volt\Component;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function getTasksProperty()
    {
        return Task::with('project')
            ->whereNotNull('deadline')
            ->get()
            ->map(function ($task) {
                // Map to FullCalendar event format
                $color = match($task->priority) {
                    'HIGH', 'CRITICAL', 'URGENT' => '#ef4444', // red
                    'MEDIUM' => '#f59e0b', // amber
                    'LOW' => '#3b82f6', // blue
                    default => '#6b7280' // gray
                };
                
                if ($task->status === 'DONE') {
                    $color = '#10b981'; // green
                }
                
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'start' => $task->deadline, // Using deadline as start date for simplicity
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'status' => $task->status,
                        'project' => $task->project->name ?? 'No Project',
                    ]
                ];
            });
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-sm"
     x-data="{ 
        events: {{ json_encode($this->tasks) }},
        selectedTask: { title: '', project: '', status: '' },
        initCalendar() {
            if (typeof FullCalendar === 'undefined') {
                setTimeout(() => this.initCalendar(), 100);
                return;
            }
            
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: this.events,
                eventClick: (info) => {
                    window.Livewire.dispatch('openTaskDetails', { taskId: info.event.id });
                    // Use Flux modal event
                    window.dispatchEvent(new CustomEvent('modal-show', { detail: { name: 'task-details' } }));
                },
                height: 700
            });
            calendar.render();
        }
     }"
     x-init="initCalendar()"
>
    <!-- FullCalendar CSS and JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    
    <style>
        /* Custom tweaks for FullCalendar inside dark mode */
        .fc {
            --fc-page-bg-color: transparent;
            --fc-neutral-bg-color: transparent;
            --fc-neutral-text-color: inherit;
            --fc-border-color: #e5e7eb;
            --fc-button-text-color: #374151;
            --fc-button-bg-color: #f3f4f6;
            --fc-button-border-color: #d1d5db;
            --fc-button-hover-bg-color: #e5e7eb;
            --fc-button-hover-border-color: #d1d5db;
            --fc-button-active-bg-color: #d1d5db;
            --fc-button-active-border-color: #9ca3af;
            
            font-family: inherit;
        }
        
        :is(.dark) .fc {
            --fc-border-color: #3f3f46;
            --fc-button-text-color: #e4e4e7;
            --fc-button-bg-color: #27272a;
            --fc-button-border-color: #3f3f46;
            --fc-button-hover-bg-color: #3f3f46;
            --fc-button-hover-border-color: #52525b;
            --fc-button-active-bg-color: #52525b;
            --fc-button-active-border-color: #71717a;
            --fc-list-event-hover-bg-color: #27272a;
        }
        
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--fc-border-color);
        }
        
        .fc-col-header-cell {
            padding: 8px 0;
            background-color: #f9fafb;
        }
        
        :is(.dark) .fc-col-header-cell {
            background-color: #18181b;
        }
        
        .fc .fc-button-primary {
            text-transform: capitalize;
            font-weight: 500;
        }
        
        .fc-event {
            cursor: pointer;
            border-radius: 4px;
            padding: 2px 4px;
            font-size: 0.75rem;
            border: none;
        }
        
        .fc-daygrid-event-dot {
            display: none;
        }
    </style>

    <div id="calendar" class="text-zinc-800 dark:text-zinc-200" wire:ignore></div>
    
    <!-- Removed the old static modal since we use task-details-modal -->
</div>
