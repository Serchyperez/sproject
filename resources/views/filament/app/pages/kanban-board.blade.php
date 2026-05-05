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

        @if($project = $this->getProject())
            <div id="kanban-board" class="flex gap-4 overflow-x-auto pb-4" style="min-height: 70vh">
                @foreach($project->taskStatuses as $status)
                    <div class="flex-shrink-0 w-72">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $status->color }}"></span>
                                <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $status->name }}</span>
                                <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-full px-2 py-0.5">
                                    {{ $status->tasks->count() }}
                                    @if($status->wip_limit) / {{ $status->wip_limit }} @endif
                                </span>
                            </div>
                        </div>

                        <div class="kanban-column space-y-2 min-h-16 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50"
                             data-status="{{ $status->id }}">
                            @foreach($status->tasks->sortBy('position') as $task)
                                <div class="kanban-card bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab hover:shadow-md transition-shadow"
                                     data-task="{{ $task->id }}">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <span class="text-xs font-medium px-1.5 py-0.5 rounded
                                            {{ $task->priority === 'urgent' ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-700' : '' }}
                                            {{ $task->priority === 'medium' ? 'bg-blue-100 text-blue-700' : '' }}
                                            {{ $task->priority === 'low' ? 'bg-gray-100 text-gray-600' : '' }}
                                        ">{{ ucfirst($task->priority) }}</span>
                                        <span class="text-xs text-gray-400">{{ $task->type }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        @if($task->due_date)
                                            <span class="text-xs text-gray-400">{{ $task->due_date->format('d/m') }}</span>
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
        @else
            <div class="text-center py-16 text-gray-500">
                <x-heroicon-o-view-columns class="w-12 h-12 mx-auto mb-3 opacity-50"/>
                <p>Selecciona un proyecto para ver el tablero Kanban</p>
            </div>
        @endif
    </div>

    @script
    <script>
        document.querySelectorAll('.kanban-column').forEach(column => {
            new Sortable(column, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'opacity-50',
                dragClass: 'shadow-2xl',
                onEnd: function (evt) {
                    const taskId = parseInt(evt.item.dataset.task);
                    const statusId = parseInt(evt.to.dataset.status);
                    const position = evt.newIndex;
                    $wire.call('moveTask', taskId, statusId, position);
                }
            });
        });
    </script>
    @endscript
</x-filament-panels::page>
