<x-filament-panels::page>
    <div>
        <div class="mb-4 flex items-center gap-3">
            <select wire:model.live="projectId"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
                @foreach($this->getProjects() as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 overflow-x-auto">
            <div id="gantt-container" wire:ignore></div>
        </div>
    </div>

    @push('styles')
    <style>
        .gantt .bar-wrapper.milestone .bar { fill: #f59e0b; }
        .gantt .bar-wrapper.priority-urgent .bar { fill: #ef4444; }
        .gantt .bar-wrapper.priority-high .bar { fill: #f97316; }
        .gantt .bar-wrapper.priority-medium .bar { fill: #6366f1; }
        .gantt .bar-wrapper.priority-low .bar { fill: #6b7280; }
    </style>
    @endpush

    @script
    <script>
        const tasks = @json($this->getGanttData());

        function renderGantt() {
            const container = document.getElementById('gantt-container');
            if (!container) return;
            container.innerHTML = '';

            if (!tasks || tasks.length === 0) {
                container.innerHTML = '<div class="text-center py-16 text-gray-500"><p>No hay tareas con fechas definidas en este proyecto</p></div>';
                return;
            }

            new Gantt('#gantt-container', tasks, {
                view_mode: 'Week',
                date_format: 'YYYY-MM-DD',
                on_click: function (task) {},
                on_date_change: function (task, start, end) {
                    if (task.id.startsWith('task-')) {
                        const id = task.id.replace('task-', '');
                        $wire.dispatch('task-dates-changed', { id, start, end });
                    }
                },
            });
        }

        renderGantt();

        $wire.on('task-moved', () => renderGantt());
    </script>
    @endscript
</x-filament-panels::page>
