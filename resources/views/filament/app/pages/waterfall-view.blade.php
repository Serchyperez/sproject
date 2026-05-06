<x-filament-panels::page>
    {{-- ── Toolbar ── --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <select wire:model.live="projectId"
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
            @foreach($this->getProjects() as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>

        <div class="ml-auto flex overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600 text-sm">
            <button wire:click="$set('viewMode','gantt')"
                    class="flex items-center gap-1.5 px-3 py-1.5 transition-colors
                           {{ $viewMode === 'gantt' ? 'bg-violet-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <x-heroicon-o-chart-bar class="w-4 h-4"/>
                Gantt
            </button>
            <button wire:click="$set('viewMode','list')"
                    class="flex items-center gap-1.5 px-3 py-1.5 border-l border-gray-300 dark:border-gray-600 transition-colors
                           {{ $viewMode === 'list' ? 'bg-violet-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <x-heroicon-o-list-bullet class="w-4 h-4"/>
                Lista
            </button>
        </div>
    </div>

    {{-- ── Label filter + manage ── --}}
    @php $labels = $this->getLabels(); $canManage = $this->canManageLabels(); @endphp

    @if ($labels->isNotEmpty() || $canManage)
    <div x-data="{ labelMgrOpen: false }" class="mb-4 space-y-2">
        {{-- Filter chips row --}}
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="clearLabels"
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors
                           {{ empty($activeLabels) ? 'bg-violet-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                Todas
            </button>

            @foreach ($labels as $label)
                @php $active = in_array($label->id, $activeLabels); @endphp
                <button wire:click="toggleLabel({{ $label->id }})"
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium border transition-all"
                        style="{{ $active
                            ? 'background-color:'.$label->color.';color:#fff;border-color:'.$label->color
                            : 'border-color:'.$label->color.';color:'.$label->color }}">
                    {{ $label->name }}
                </button>
            @endforeach

            @if ($canManage)
                <button @click="labelMgrOpen = !labelMgrOpen"
                        class="ml-auto flex items-center gap-1 text-xs text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                    <x-heroicon-o-tag class="w-3.5 h-3.5"/>
                    <span x-text="labelMgrOpen ? 'Ocultar' : 'Gestionar etiquetas'"></span>
                </button>
            @endif
        </div>

        {{-- Label manager panel --}}
        @if ($canManage)
        <div x-show="labelMgrOpen" x-transition
             class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-3">Gestión de etiquetas</p>

            {{-- Existing labels --}}
            <div class="flex flex-wrap gap-2 mb-3">
                @forelse ($labels as $label)
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                          style="background-color: {{ $label->color }}">
                        {{ $label->name }}
                        <button wire:click="deleteLabel({{ $label->id }})"
                                wire:confirm="¿Eliminar la etiqueta '{{ $label->name }}'? Se eliminará de todas las tareas."
                                class="hover:opacity-70 -mr-0.5">
                            <x-heroicon-o-x-mark class="w-3 h-3"/>
                        </button>
                    </span>
                @empty
                    <span class="text-xs text-gray-400">Sin etiquetas todavía</span>
                @endforelse
            </div>

            {{-- Create form --}}
            <form wire:submit="createLabel" class="flex items-center gap-2">
                <input wire:model="newLabelName"
                       type="text"
                       placeholder="Nombre de etiqueta…"
                       maxlength="50"
                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-violet-500"/>
                <input wire:model="newLabelColor"
                       type="color"
                       class="h-8 w-8 cursor-pointer rounded border border-gray-300 dark:border-gray-600 p-0.5"/>
                <button type="submit"
                        class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-700 transition-colors">
                    Añadir
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif

    {{-- ── No project ── --}}
    @if (!$projectId)
        <div class="py-16 text-center text-gray-500">
            <x-heroicon-o-funnel class="mx-auto mb-3 h-12 w-12 opacity-50"/>
            <p>No tienes proyectos disponibles.</p>
        </div>
    @elseif ($viewMode === 'gantt')

    {{-- ── Gantt view ── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div id="waterfall-gantt" wire:ignore class="min-h-[320px] p-4 overflow-x-auto"></div>
    </div>

    @else

    {{-- ── List view ── --}}
    @php $groups = $this->getListData(); @endphp

    @if (empty($groups))
        <div class="py-16 text-center text-gray-500">
            <x-heroicon-o-funnel class="mx-auto mb-3 h-12 w-12 opacity-50"/>
            <p>No hay tareas en este proyecto.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($groups as $group)
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    {{-- Group header --}}
                    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-4 py-2.5">
                        @if ($group['label'])
                            <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                  style="background-color: {{ $group['label']->color }}"></span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $group['label']->name }}</span>
                        @else
                            <span class="text-xs font-semibold text-gray-400">Sin etiqueta</span>
                        @endif
                        <span class="ml-auto text-xs text-gray-400">{{ count($group['tasks']) }} {{ count($group['tasks']) === 1 ? 'tarea' : 'tareas' }}</span>
                    </div>

                    {{-- Tasks --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                        @foreach ($group['tasks'] as $task)
                            @php
                                $isMilestone = $task->start_date && $task->due_date
                                    && $task->start_date->format('Y-m-d') === $task->due_date->format('Y-m-d');
                            @endphp
                            <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                {{-- Priority / milestone indicator --}}
                                @if ($isMilestone)
                                    <span class="flex-shrink-0 text-amber-500 text-sm">♦</span>
                                @else
                                    <span class="h-2 w-2 flex-shrink-0 rounded-full
                                        {{ $task->priority === 'urgent' ? 'bg-red-500' : '' }}
                                        {{ $task->priority === 'high'   ? 'bg-orange-500' : '' }}
                                        {{ $task->priority === 'medium' ? 'bg-blue-500' : '' }}
                                        {{ $task->priority === 'low'    ? 'bg-gray-400' : '' }}
                                    "></span>
                                @endif

                                {{-- Title + predecessor --}}
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                        {{ $task->title }}
                                    </p>
                                    @if ($task->predecessor)
                                        <p class="text-xs text-gray-400">
                                            <x-heroicon-o-arrow-right class="inline w-3 h-3"/> {{ $task->predecessor->title }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Meta --}}
                                <div class="flex flex-shrink-0 items-center gap-3 text-xs text-gray-500">
                                    {{-- Dates --}}
                                    @if ($task->start_date || $task->due_date)
                                        <span class="hidden sm:flex items-center gap-1">
                                            <x-heroicon-o-calendar class="h-3.5 w-3.5"/>
                                            @if ($task->start_date && $task->due_date && !$isMilestone)
                                                {{ $task->start_date->format('d/m') }} → {{ $task->due_date->format('d/m/Y') }}
                                            @elseif ($task->due_date)
                                                {{ $task->due_date->format('d/m/Y') }}
                                            @else
                                                {{ $task->start_date->format('d/m/Y') }}
                                            @endif
                                        </span>
                                    @endif

                                    {{-- Assignee --}}
                                    @if ($task->assignee)
                                        <span class="hidden sm:block rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                            {{ $task->assignee->name }}
                                        </span>
                                    @endif

                                    {{-- Status --}}
                                    @if ($task->status)
                                        <span class="rounded-full px-2 py-0.5 font-medium"
                                              style="background-color:{{ $task->status->color }}22;color:{{ $task->status->color }}">
                                            {{ $task->status->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @endif

    @push('styles')
    <style>
        /* Milestone bars — diamond via rotate */
        .gantt .bar-wrapper.gantt-milestone .bar {
            fill: #f59e0b;
            transform: rotate(45deg);
            transform-box: fill-box;
            transform-origin: center;
        }
        .gantt .bar-wrapper.gantt-milestone .bar-progress { display: none; }
        /* Priority colours */
        .gantt .bar-wrapper.priority-urgent .bar { fill: #ef4444; }
        .gantt .bar-wrapper.priority-high   .bar { fill: #f97316; }
        .gantt .bar-wrapper.priority-medium .bar { fill: #6366f1; }
        .gantt .bar-wrapper.priority-low    .bar { fill: #6b7280; }
    </style>
    @endpush

    @script
    <script>
        let ganttData = @json($this->getGanttData());

        function renderGantt(data) {
            if (data !== undefined) ganttData = data;
            const container = document.getElementById('waterfall-gantt');
            if (!container) return;
            container.innerHTML = '';

            if (!ganttData || ganttData.length === 0) {
                container.innerHTML =
                    '<div class="flex flex-col items-center justify-center py-16 text-gray-400">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2a4 4 0 014-4h2m-6 6h6m-3-10V5m0 0a2 2 0 100-4 2 2 0 000 4z"/></svg>' +
                    '<p class="text-sm">No hay tareas con fechas definidas en este proyecto.</p>' +
                    '<p class="text-xs mt-1 text-gray-300">Asigna una fecha de inicio o fin a las tareas para verlas aquí.</p>' +
                    '</div>';
                return;
            }

            new Gantt('#waterfall-gantt', ganttData, {
                view_mode: 'Week',
                date_format: 'YYYY-MM-DD',
                on_click: function (task) {},
                on_date_change: function (task, start, end) {
                    if (!task.id.startsWith('task-')) return;
                    const id = parseInt(task.id.replace('task-', ''));
                    const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                    $wire.call('updateTaskDates', id, fmt(start), fmt(end));
                },
            });
        }

        renderGantt();

        $wire.on('gantt-refresh', ({tasks}) => renderGantt(tasks));
    </script>
    @endscript
</x-filament-panels::page>
