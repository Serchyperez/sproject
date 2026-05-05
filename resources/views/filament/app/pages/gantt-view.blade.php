<x-filament-panels::page>
    <div class="mb-4 flex items-center gap-3">
        <select wire:model.live="projectId"
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
            @foreach($this->getProjects() as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 overflow-x-auto">
        <div id="gantt-container"></div>
    </div>

    <style>
        .gantt .bar-wrapper.milestone .bar {
            fill: #f59e0b;
        }
        .gantt .bar-wrapper.priority-urgent .bar { fill: #ef4444; }
        .gantt .bar-wrapper.priority-high .bar { fill: #f97316; }
        .gantt .bar-wrapper.priority-medium .bar { fill: #6366f1; }
        .gantt .bar-wrapper.priority-low .bar { fill: #6b7280; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tasks = @json($this->getGanttData());

        if (tasks.length === 0) {
            document.getElementById('gantt-container').innerHTML =
                '<div class="text-center py-16 text-gray-500"><p>No hay tareas con fechas definidas en este proyecto</p></div>';
            return;
        }

        const gantt = new Gantt('#gantt-container', tasks, {
            view_mode: 'Week',
            date_format: 'YYYY-MM-DD',
            language: 'es',
            on_click: function (task) {
                console.log(task);
            },
            on_date_change: function (task, start, end) {
                const id = task.id.replace('task-', '');
                if (task.id.startsWith('task-')) {
                    @this.dispatch('task-dates-changed', { id, start, end });
                }
            },
        });
    });
    </script>
</x-filament-panels::page>
