<x-filament-panels::page>
@php
    $project   = $this->getProject();
    $sprint    = $this->getCurrentSprint();
    $stats     = $this->getSprintStats();
    $canManage = $this->canManageSprint();
@endphp

{{-- ── Toolbar ── --}}
<div x-data="{ sprintForm: false }" class="mb-4 space-y-3">

    {{-- Row: selectors + actions --}}
    <div class="flex flex-wrap items-center gap-2">

        {{-- Project selector --}}
        <select wire:model.live="projectId"
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
            @forelse ($this->getProjects() as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @empty
                <option value="">Sin proyectos Scrum</option>
            @endforelse
        </select>

        {{-- Sprint selector --}}
        @if ($project)
        <select wire:model.live="sprintId"
                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
            <option value="">— Sin sprint —</option>
            @foreach ($project->sprints as $s)
                <option value="{{ $s->id }}">{{ $s->name }}
                    @if ($s->status === 'active') · Activo
                    @elseif ($s->status === 'planning') · Planificación
                    @else · Completado
                    @endif
                </option>
            @endforeach
        </select>
        @endif

        {{-- Backlog toggle --}}
        @if ($project)
        @php $backlog = $this->getBacklogItems(); $backlogCount = $backlog['stories']->count() + $backlog['tasks']->count(); @endphp
        <button wire:click="$toggle('showBacklog')"
                class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm transition-colors
                       {{ $showBacklog ? 'border-violet-400 bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
            <x-heroicon-o-queue-list class="h-4 w-4"/>
            Backlog
            @if ($backlogCount > 0)
                <span class="rounded-full bg-gray-200 dark:bg-gray-600 px-1.5 text-xs">{{ $backlogCount }}</span>
            @endif
        </button>
        @endif

        {{-- Sprint management --}}
        @if ($canManage && $project)
        <div class="ml-auto flex items-center gap-2">
            @if ($sprint?->status === 'planning')
                <button wire:click="startSprint"
                        wire:confirm="¿Iniciar '{{ $sprint->name }}'? Se marcará como sprint activo."
                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 transition-colors">
                    Iniciar sprint
                </button>
            @elseif ($sprint?->status === 'active')
                <button wire:click="completeSprint"
                        wire:confirm="¿Completar '{{ $sprint->name }}'? Las tareas pendientes vuelven al backlog."
                        class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600 transition-colors">
                    Completar sprint
                </button>
            @endif

            <button @click="sprintForm = !sprintForm"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <x-heroicon-o-plus class="h-3.5 w-3.5"/>
                Nuevo sprint
            </button>
        </div>
        @endif
    </div>

    {{-- Sprint info bar --}}
    @if ($sprint)
    @php
        $statusColor = match($sprint->status) {
            'active'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
            'planning'  => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            'completed' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
        };
        $statusLabel = match($sprint->status) {
            'active'    => 'Activo',
            'planning'  => 'Planificación',
            'completed' => 'Completado',
        };
    @endphp
    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 px-4 py-2.5 text-sm">
        <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $sprint->name }}</span>
        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $statusLabel }}</span>

        @if ($sprint->goal)
            <span class="text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $sprint->goal }}</span>
        @endif

        @if ($sprint->start_date || $sprint->end_date)
            <span class="hidden sm:flex items-center gap-1 text-xs text-gray-400">
                <x-heroicon-o-calendar class="h-3.5 w-3.5"/>
                @if ($sprint->start_date && $sprint->end_date)
                    {{ $sprint->start_date->format('d M') }} → {{ $sprint->end_date->format('d M Y') }}
                @elseif ($sprint->end_date)
                    hasta {{ $sprint->end_date->format('d M Y') }}
                @endif
            </span>
        @endif

        <div class="ml-auto flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            {{-- Task counter --}}
            <span>{{ $stats['done_count'] }} / {{ $stats['count'] }} tareas</span>

            {{-- Velocity bar --}}
            @if ($stats['planned'] > 0)
            <div class="hidden sm:flex items-center gap-1.5">
                <span class="text-violet-600 dark:text-violet-400 font-medium">
                    {{ $stats['done'] }} / {{ $stats['planned'] }} pts
                </span>
                <div class="w-20 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    <div class="h-full rounded-full bg-violet-500 transition-all"
                         style="width: {{ $stats['planned'] > 0 ? round($stats['done'] / $stats['planned'] * 100) : 0 }}%">
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Create sprint form --}}
    <div x-show="sprintForm" x-transition class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-3">Nuevo sprint</p>
        <form wire:submit="createSprint" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="lg:col-span-2">
                <input wire:model="newSprintName" type="text" placeholder="Nombre del sprint *" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-violet-500"/>
            </div>
            <div class="lg:col-span-2">
                <input wire:model="newSprintGoal" type="text" placeholder="Objetivo (opcional)"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-violet-500"/>
            </div>
            <input wire:model="newSprintStart" type="date"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-violet-500"/>
            <input wire:model="newSprintEnd" type="date"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-violet-500"/>
            <div class="sm:col-span-2 flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-violet-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-violet-700 transition-colors">
                    Crear sprint
                </button>
                <button type="button" @click="sprintForm = false"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>

</div>{{-- /toolbar x-data --}}

{{-- ── No project ── --}}
@if (!$project)
    <div class="py-16 text-center text-gray-500">
        <x-heroicon-o-arrow-path class="mx-auto mb-3 h-12 w-12 opacity-50"/>
        <p>No tienes proyectos con metodología Scrum.</p>
    </div>

@else

{{-- ── Main grid: [Backlog |] Board ── --}}
<div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

    {{-- ─── Backlog panel ─────────────────────────────────────────── --}}
    @if ($showBacklog)
    <div class="xl:col-span-1 space-y-3" x-data="{ storyForm: false }">

        {{-- Backlog header --}}
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Backlog</h3>
            @if ($canManage)
            <button @click="storyForm = !storyForm"
                    class="flex items-center gap-1 text-xs text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                <x-heroicon-o-plus class="h-3.5 w-3.5"/>
                Historia
            </button>
            @endif
        </div>

        {{-- Create story form --}}
        <div x-show="storyForm" x-transition>
            <form wire:submit="createStory" class="flex gap-2">
                <input wire:model="newStoryTitle" type="text" placeholder="Título de la historia…" required
                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-violet-500"/>
                <button type="submit"
                        class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-700">
                    Añadir
                </button>
            </form>
        </div>

        @php $backlogItems = $this->getBacklogItems(); @endphp

        {{-- Stories --}}
        @forelse ($backlogItems['stories'] as $story)
        <div wire:key="story-{{ $story->id }}"
             x-data="{ open: false }"
             class="overflow-hidden rounded-xl border border-violet-200 dark:border-violet-800/50 bg-white dark:bg-gray-800 shadow-sm">

            {{-- Story header --}}
            <div class="flex items-start gap-2 px-3 py-2.5 cursor-pointer hover:bg-violet-50/50 dark:hover:bg-violet-900/20 transition-colors"
                 @click="open = !open">
                <span :class="open ? 'rotate-90' : ''" class="flex-shrink-0 transition-transform inline-flex mt-0.5">
                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400"/>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-snug">{{ $story->title }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">
                        <span>{{ $story->subtasks->count() }} tareas</span>
                        @if ($story->subtasks->sum('story_points') > 0)
                            <span class="rounded bg-violet-100 dark:bg-violet-900/40 px-1 text-violet-600 dark:text-violet-300">
                                {{ $story->subtasks->sum('story_points') }} pts
                            </span>
                        @endif
                    </div>
                </div>
                @if ($sprintId)
                    <button wire:click.stop="addStoryToSprint({{ $story->id }})"
                            title="Añadir historia y tareas al sprint"
                            class="flex-shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-medium text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors">
                        → Sprint
                    </button>
                @endif
            </div>

            {{-- Story subtasks --}}
            <div x-show="open" x-transition
                 class="divide-y divide-gray-50 dark:divide-gray-700/50 border-t border-violet-100 dark:border-violet-800/30">
                @forelse ($story->subtasks as $task)
                    <div class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full
                            {{ $task->priority === 'urgent' ? 'bg-red-500' : '' }}
                            {{ $task->priority === 'high'   ? 'bg-orange-500' : '' }}
                            {{ $task->priority === 'medium' ? 'bg-blue-500' : '' }}
                            {{ $task->priority === 'low'    ? 'bg-gray-400' : '' }}
                        "></span>
                        <p class="flex-1 text-xs text-gray-700 dark:text-gray-300 truncate">{{ $task->title }}</p>
                        @if ($task->story_points)
                            <span class="text-[10px] text-gray-400">{{ $task->story_points }}p</span>
                        @endif
                        @if ($sprintId)
                            <button wire:click="addToSprint({{ $task->id }})"
                                    class="text-[10px] text-violet-500 hover:text-violet-700 transition-colors">→</button>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-2 text-xs text-gray-400">Sin tareas aún</div>
                @endforelse
            </div>
        </div>
        @empty
            @if ($backlogItems['tasks']->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 py-8 text-center text-xs text-gray-400">
                    El backlog está vacío
                </div>
            @endif
        @endforelse

        {{-- Standalone tasks --}}
        @if ($backlogItems['tasks']->isNotEmpty())
        <div class="space-y-1.5">
            <p class="text-xs font-medium text-gray-400 px-1">Tareas sueltas</p>
            @foreach ($backlogItems['tasks'] as $task)
                <div wire:key="btask-{{ $task->id }}"
                     class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 shadow-sm">
                    <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full
                        {{ $task->priority === 'urgent' ? 'bg-red-500' : '' }}
                        {{ $task->priority === 'high'   ? 'bg-orange-500' : '' }}
                        {{ $task->priority === 'medium' ? 'bg-blue-500' : '' }}
                        {{ $task->priority === 'low'    ? 'bg-gray-400' : '' }}
                    "></span>
                    <p class="flex-1 text-xs text-gray-700 dark:text-gray-300 truncate">{{ $task->title }}</p>
                    @if ($task->story_points)
                        <span class="text-[10px] text-gray-400">{{ $task->story_points }}p</span>
                    @endif
                    @if ($sprintId)
                        <button wire:click="addToSprint({{ $task->id }})"
                                class="text-[10px] text-violet-500 hover:text-violet-700 transition-colors">
                            → Sprint
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

    </div>{{-- /backlog --}}

    <div class="xl:col-span-3">
    @else
    <div class="xl:col-span-4">
    @endif

        {{-- ─── Sprint board ─────────────────────────────────────── --}}
        @if (!$sprintId)
            <div class="py-16 text-center text-gray-500">
                <x-heroicon-o-arrow-path class="mx-auto mb-3 h-12 w-12 opacity-50"/>
                <p>Selecciona o crea un sprint para ver el tablero.</p>
            </div>
        @else
            @php $sprintTasks = $this->getSprintTasks(); @endphp
            <div class="flex gap-4 overflow-x-auto pb-4">
                @foreach ($project->taskStatuses as $status)
                    <div class="w-64 flex-shrink-0">
                        {{-- Column header --}}
                        <div class="mb-3 flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full" style="background-color: {{ $status->color }}"></span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $status->name }}</span>
                            <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-500">
                                {{ count($sprintTasks[$status->id] ?? []) }}
                            </span>
                            @if ($status->wip_limit && count($sprintTasks[$status->id] ?? []) > $status->wip_limit)
                                <span class="rounded-full bg-red-100 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-600 dark:text-red-400">
                                    WIP!
                                </span>
                            @endif
                        </div>

                        {{-- Drop zone --}}
                        <div class="scrum-column min-h-16 space-y-2 rounded-xl bg-gray-50 dark:bg-gray-800/50 p-2"
                             data-status="{{ $status->id }}">
                            @foreach ($sprintTasks[$status->id] ?? [] as $task)
                                <div wire:key="t-{{ $task->id }}"
                                     class="group cursor-grab rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 shadow-sm hover:shadow-md transition-shadow select-none"
                                     data-task="{{ $task->id }}">
                                    <div class="mb-1.5 flex items-start justify-between gap-1">
                                        @if ($task->parent)
                                            <span class="inline-block rounded bg-violet-50 dark:bg-violet-900/30 px-1.5 py-0.5 text-[10px] font-medium text-violet-600 dark:text-violet-300">
                                                {{ Str::limit($task->parent->title, 28) }}
                                            </span>
                                        @else
                                            <span></span>
                                        @endif
                                        <button @click.stop="Livewire.dispatch('open-task-modal', {taskId: {{ $task->id }}})"
                                                class="invisible group-hover:visible flex-shrink-0 rounded p-0.5 text-gray-400 hover:text-violet-600 transition-colors"
                                                title="Ver detalle">
                                            <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5"/>
                                        </button>
                                    </div>
                                    <p class="text-sm font-medium leading-snug text-gray-800 dark:text-gray-100">{{ $task->title }}</p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            @if ($task->story_points)
                                                <span class="rounded bg-violet-100 dark:bg-violet-900/40 px-1.5 py-0.5 text-[10px] font-medium text-violet-700 dark:text-violet-300">
                                                    {{ $task->story_points }} pts
                                                </span>
                                            @endif
                                            <span class="h-1.5 w-1.5 rounded-full
                                                {{ $task->priority === 'urgent' ? 'bg-red-500' : '' }}
                                                {{ $task->priority === 'high'   ? 'bg-orange-500' : '' }}
                                                {{ $task->priority === 'medium' ? 'bg-blue-400' : '' }}
                                                {{ $task->priority === 'low'    ? 'bg-gray-300' : '' }}
                                            "></span>
                                        </div>
                                        @if ($task->assignee)
                                            <span class="text-[10px] text-gray-400">{{ $task->assignee->name }}</span>
                                        @endif
                                    </div>
                                    {{-- Remove from sprint --}}
                                    <button wire:click="removeFromSprint({{ $task->id }})"
                                            class="mt-1.5 text-[10px] text-gray-300 hover:text-red-400 transition-colors">
                                        ✕ quitar del sprint
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>{{-- /board column --}}
</div>{{-- /main grid --}}

@endif{{-- /project --}}

    <livewire:task-detail-modal />

    @script
    <script>
        document.querySelectorAll('.scrum-column').forEach(column => {
            new Sortable(column, {
                group:       'scrum',
                animation:   150,
                ghostClass:  'opacity-40',
                dragClass:   'shadow-xl',
                onEnd: function (evt) {
                    const taskId   = parseInt(evt.item.dataset.task);
                    const statusId = parseInt(evt.to.dataset.status);
                    $wire.call('moveTask', taskId, statusId);
                },
            });
        });
    </script>
    @endscript
</x-filament-panels::page>
