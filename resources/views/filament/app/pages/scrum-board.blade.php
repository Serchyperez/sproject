<x-filament-panels::page>
    <div>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            @if($project = $this->getProject())
                <select wire:model.live="projectId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                </select>
                <select wire:model.live="sprintId"
                        class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
                    @foreach($project->sprints as $sprint)
                        <option value="{{ $sprint->id }}">{{ $sprint->name }} ({{ ucfirst($sprint->status) }})</option>
                    @endforeach
                </select>
                <button wire:click="$toggle('showBacklog')"
                        class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                    {{ $showBacklog ? 'Ocultar Backlog' : 'Ver Backlog' }}
                </button>
            @endif
        </div>

        @if($project = $this->getProject())
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                @if($showBacklog)
                    <div class="xl:col-span-1">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-3">Backlog</h3>
                        <div id="backlog-list" class="space-y-2 min-h-16 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                            @foreach($this->getBacklogTasks() as $task)
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700"
                                     data-task="{{ $task->id }}">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                    <div class="flex justify-between mt-1">
                                        <span class="text-xs text-gray-400">{{ $task->type }}</span>
                                        @if($sprintId)
                                            <button wire:click="addToSprint({{ $task->id }})"
                                                    class="text-xs text-violet-600 hover:text-violet-800">+ Sprint →</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="xl:col-span-3">
                @else
                    <div class="xl:col-span-4">
                @endif
                    <div class="flex gap-4 overflow-x-auto">
                        @foreach($project->taskStatuses as $status)
                            <div class="flex-shrink-0 w-64">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $status->color }}"></span>
                                    <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $status->name }}</span>
                                    <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-full px-2 py-0.5">
                                        {{ count($this->getSprintTasks()[$status->id] ?? []) }}
                                    </span>
                                </div>
                                <div class="scrum-column space-y-2 min-h-16 p-2 rounded-xl bg-gray-50 dark:bg-gray-800/50"
                                     data-status="{{ $status->id }}">
                                    @foreach($this->getSprintTasks()[$status->id] ?? [] as $task)
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-700 cursor-grab"
                                             data-task="{{ $task->id }}">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                            <div class="flex justify-between mt-1">
                                                @if($task->story_points)
                                                    <span class="text-xs bg-violet-100 text-violet-700 rounded px-1">{{ $task->story_points }} pts</span>
                                                @endif
                                                @if($task->assignee)
                                                    <span class="text-xs text-gray-400">{{ $task->assignee->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-16 text-gray-500">
                <p>Selecciona un proyecto Scrum para ver el tablero</p>
            </div>
        @endif
    </div>

    @script
    <script>
        document.querySelectorAll('.scrum-column').forEach(column => {
            new Sortable(column, {
                group: 'scrum',
                animation: 150,
                ghostClass: 'opacity-50',
                onEnd: function (evt) {
                    const taskId = parseInt(evt.item.dataset.task);
                    const statusId = parseInt(evt.to.dataset.status);
                    $wire.call('moveTask', taskId, statusId);
                }
            });
        });
    </script>
    @endscript
</x-filament-panels::page>
