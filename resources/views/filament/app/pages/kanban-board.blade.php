<x-filament-panels::page>
    <div>
        {{-- Toolbar --}}
        <div class="mb-4 flex items-center gap-3 flex-wrap">
            {{-- Methodology icon + label --}}
            <span style="display:inline-flex;align-items:center;gap:5px;background:#dbeafe;color:#1d4ed8;border-radius:9999px;padding:3px 10px;font-size:0.75rem;font-weight:500;flex-shrink:0;">
                <x-heroicon-o-view-columns style="width:13px;height:13px;"/>
                Kanban
            </span>

            <select wire:model.live="projectId"
                    class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
                @foreach($this->getProjects() as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>

            @if($this->getProject())
                <button wire:click="toggleBacklog"
                        class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors">
                    {{ $showBacklog ? '← Ocultar Backlog' : 'Ver Backlog →' }}
                </button>

                {{-- Gantt button --}}
                <a href="{{ route('filament.app.pages.waterfall-view', ['projectId' => $this->projectId]) }}"
                   style="display:inline-flex;align-items:center;gap:5px;border:1px solid #d1d5db;border-radius:8px;padding:5px 12px;font-size:0.875rem;color:#374151;text-decoration:none;background:#fff;flex-shrink:0;"
                   class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 hover:opacity-80">
                    <x-heroicon-o-chart-bar style="width:15px;height:15px;"/>
                    Gantt
                </a>

                @if($this->canCreateTask())
                <button type="button"
                        onclick="Livewire.dispatch('open-create-task', { projectId: {{ $this->projectId }}, methodology: 'kanban' })"
                        style="display:inline-flex;align-items:center;gap:5px;background-color:#7c3aed;color:#fff;border:none;border-radius:8px;padding:5px 12px;font-size:0.875rem;cursor:pointer;flex-shrink:0;">
                    <x-heroicon-o-plus style="width:15px;height:15px;"/>
                    Nueva tarea
                </button>
                @endif
            @endif
        </div>

        @if($project = $this->getProject())
            <div class="flex gap-6">

                {{-- Backlog panel --}}
                @if($showBacklog)
                    <div class="w-64 flex-shrink-0">
                        <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                            <x-heroicon-o-inbox class="w-4 h-4"/>
                            Backlog
                            <span class="text-xs bg-gray-100 dark:bg-gray-700 rounded-full px-2 py-0.5 text-gray-500">
                                {{ $this->getBacklogTasks()->count() }}
                            </span>
                        </h3>
                        <div id="backlog-column"
                             class="kanban-column space-y-2 min-h-24 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 border-2 border-dashed border-gray-200 dark:border-gray-700"
                             data-status="">
                            @forelse($this->getBacklogTasks() as $task)
                                <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab hover:shadow-md transition-shadow"
                                     wire:key="task-{{ $task->id }}"
                                     data-task="{{ $task->id }}">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 mb-1">{{ $task->title }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs px-1.5 py-0.5 rounded
                                            {{ $task->priority === 'urgent' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-700' : '' }}
                                            {{ $task->priority === 'medium' ? 'bg-blue-100 text-blue-700' : '' }}
                                            {{ $task->priority === 'low' ? 'bg-gray-100 text-gray-600' : '' }}
                                        ">{{ ucfirst($task->priority) }}</span>
                                        <span class="text-xs text-gray-400">{{ $task->type }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400 text-center py-4">Sin tareas en backlog</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Kanban columns --}}
                <div class="flex gap-4 overflow-x-auto pb-4 flex-1" style="min-height: 70vh">
                    @foreach($project->taskStatuses as $status)
                        @php
                            $taskCount = $status->tasks->count();
                            $wipExceeded = $status->wip_limit && $taskCount > $status->wip_limit;
                        @endphp
                        <div class="flex-shrink-0 w-72" wire:key="col-{{ $status->id }}">
                            {{-- Column header --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $status->color }}"></span>
                                    <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $status->name }}</span>
                                    <span @class([
                                        'text-xs rounded-full px-2 py-0.5 font-medium',
                                        'bg-red-100 text-red-700' => $wipExceeded,
                                        'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' => !$wipExceeded,
                                    ])>
                                        {{ $taskCount }}@if($status->wip_limit) / {{ $status->wip_limit }}@endif
                                    </span>
                                </div>
                                @if($wipExceeded)
                                    <span class="text-xs text-red-600 font-medium flex items-center gap-1">
                                        <x-heroicon-s-exclamation-triangle class="w-3 h-3"/>
                                        WIP
                                    </span>
                                @endif
                                @if($this->canCreateTask())
                                <button type="button"
                                        onclick="Livewire.dispatch('open-create-task', { projectId: {{ $this->projectId }}, methodology: 'kanban', statusId: {{ $status->id }} })"
                                        title="Nueva tarea en {{ $status->name }}"
                                        style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px;display:flex;align-items:center;">
                                    <x-heroicon-o-plus style="width:14px;height:14px;"/>
                                </button>
                                @endif
                            </div>

                            {{-- WIP limit bar --}}
                            @if($status->wip_limit)
                                <div class="mb-2 h-1 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $wipExceeded ? 'bg-red-500' : 'bg-violet-400' }}"
                                         style="width: {{ min(100, ($taskCount / $status->wip_limit) * 100) }}%"></div>
                                </div>
                            @endif

                            {{-- Column body --}}
                            <div class="kanban-column space-y-2 min-h-32 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 {{ $wipExceeded ? 'ring-2 ring-red-300 dark:ring-red-800' : '' }}"
                                 data-status="{{ $status->id }}">
                                @foreach($status->tasks->sortBy('position') as $task)
                                    <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab hover:shadow-md transition-shadow"
                                         wire:key="task-{{ $task->id }}"
                                         data-task="{{ $task->id }}">
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <span class="text-xs font-medium px-1.5 py-0.5 rounded
                                                {{ $task->priority === 'urgent' ? 'bg-red-100 text-red-700' : '' }}
                                                {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-700' : '' }}
                                                {{ $task->priority === 'medium' ? 'bg-blue-100 text-blue-700' : '' }}
                                                {{ $task->priority === 'low' ? 'bg-gray-100 text-gray-600' : '' }}
                                            ">{{ ucfirst($task->priority) }}</span>
                                            <button @click.stop="Livewire.dispatch('open-task-modal', {taskId: {{ $task->id }}})"
                                                    class="rounded p-0.5 text-gray-300 hover:text-violet-600 transition-colors"
                                                    title="Ver detalle">
                                                <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5"/>
                                            </button>
                                        </div>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                        <div class="flex items-center justify-between mt-2">
                                            @if($task->due_date)
                                                <span class="text-xs text-gray-400">{{ $task->due_date->format('d/m') }}</span>
                                            @else
                                                <span></span>
                                            @endif
                                            @if($task->assignee)
                                                <span class="text-xs bg-gray-100 dark:bg-gray-700 rounded-full px-2 py-0.5 text-gray-600 dark:text-gray-300">
                                                    {{ $task->assignee->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        @else
            <div class="text-center py-16 text-gray-500">
                <x-heroicon-o-view-columns class="w-12 h-12 mx-auto mb-3 opacity-50"/>
                <p>Selecciona un proyecto para ver el tablero Kanban</p>
            </div>
        @endif
    </div>

    <livewire:task-detail-modal />
    <livewire:create-task-modal />

    @assets
    <script src="/sortable.min.js"></script>
    @endassets

    @script
    <script>
        const clearDropHighlight = () => {
            document.querySelectorAll('.kanban-column').forEach(c => c.classList.remove('kanban-drop-active'));
        };

        // Ctrl+Z / Cmd+Z — undo last completed move
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                $wire.call('undoLastMove');
            }
        });

        const initKanban = () => {
            document.querySelectorAll('.kanban-column').forEach(column => {
                if (column._sortable) column._sortable.destroy();
                column._sortable = new Sortable(column, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'kanban-ghost',
                    dragClass: 'shadow-2xl',
                    emptyInsertThreshold: 40,
                    onMove: (evt) => {
                        clearDropHighlight();
                        if (evt.to) evt.to.classList.add('kanban-drop-active');
                    },
                    onEnd: (evt) => {
                        clearDropHighlight();

                        // SortableJS fires onEnd from the native 'drop' event on a
                        // successful move, but from 'dragend' when the drag was
                        // cancelled (Escape key or released outside a valid zone).
                        if (evt.originalEvent?.type === 'dragend') {
                            $wire.call('cancelDrag');
                            return;
                        }

                        const taskId = parseInt(evt.item.dataset.task);
                        const rawStatus = evt.to.dataset.status;
                        const statusId = rawStatus === '' ? null : parseInt(rawStatus);
                        const position = evt.newIndex;

                        if (statusId === null) {
                            $wire.call('moveToBacklog', taskId);
                        } else {
                            $wire.call('moveTask', taskId, statusId, position);
                        }
                    }
                });
            });
        };

        initKanban();
        $wire.on('task-moved', () => initKanban());
        $wire.on('backlog-toggled', () => setTimeout(initKanban, 50));
    </script>
    @endscript
</x-filament-panels::page>
