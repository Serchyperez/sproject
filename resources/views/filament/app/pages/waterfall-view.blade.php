<x-filament-panels::page>
    <div class="mb-4 flex items-center gap-3">
        <select wire:model.live="projectId"
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm">
            @foreach(\App\Models\Project::where('owner_id', auth()->id())
                ->orWhereHas('members', fn($q) => $q->where('user_id', auth()->id()))
                ->get() as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="space-y-6">
        @forelse($this->getPhases() as $phase)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                     style="border-left: 4px solid {{ $phase->color }}">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-flag class="w-4 h-4" style="color: {{ $phase->color }}"/>
                            {{ $phase->name }}
                        </h3>
                        @if($phase->description)
                            <p class="text-sm text-gray-500 mt-0.5">{{ $phase->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        @if($phase->due_date)
                            <span class="text-gray-500">
                                <x-heroicon-o-calendar class="w-4 h-4 inline mr-1"/>
                                {{ $phase->due_date->format('d/m/Y') }}
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $phase->status === 'pending' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $phase->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $phase->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                        ">
                            {{ $phase->status === 'pending' ? 'Pendiente' : ($phase->status === 'in_progress' ? 'En progreso' : 'Completado') }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @forelse($phase->tasks as $task)
                        <div class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full flex-shrink-0
                                    {{ $task->priority === 'urgent' ? 'bg-red-500' : '' }}
                                    {{ $task->priority === 'high' ? 'bg-orange-500' : '' }}
                                    {{ $task->priority === 'medium' ? 'bg-blue-500' : '' }}
                                    {{ $task->priority === 'low' ? 'bg-gray-400' : '' }}
                                "></span>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                    @if($task->estimated_hours)
                                        <p class="text-xs text-gray-400">{{ $task->estimated_hours }}h estimadas</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-500">
                                @if($task->assignee)
                                    <span class="text-xs bg-gray-100 dark:bg-gray-700 rounded-full px-2 py-0.5">
                                        {{ $task->assignee->name }}
                                    </span>
                                @endif
                                @if($task->due_date)
                                    <span class="text-xs">{{ $task->due_date->format('d/m/Y') }}</span>
                                @endif
                                @if($task->status)
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                          style="background-color: {{ $task->status->color }}22; color: {{ $task->status->color }}">
                                        {{ $task->status->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-4 text-sm text-gray-400">Sin tareas en esta fase</div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="text-center py-16 text-gray-500">
                <x-heroicon-o-funnel class="w-12 h-12 mx-auto mb-3 opacity-50"/>
                <p>Define hitos para estructurar las fases del proyecto Waterfall</p>
            </div>
        @endforelse

        @if($unassigned = $this->getUnassignedTasks() and $unassigned->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-500">Sin fase asignada</h3>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach($unassigned as $task)
                        <div class="px-5 py-3 flex items-center justify-between">
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $task->title }}</p>
                            @if($task->assignee)
                                <span class="text-xs text-gray-400">{{ $task->assignee->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
