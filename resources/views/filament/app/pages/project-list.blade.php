<x-filament-panels::page>

@php
    $methodologyLabels = ['kanban' => 'Kanban', 'scrum' => 'Scrum', 'waterfall' => 'Waterfall'];
    $methodologyColors = [
        'kanban'    => 'background:#dbeafe;color:#1d4ed8',
        'scrum'     => 'background:#ede9fe;color:#6d28d9',
        'waterfall' => 'background:#fef3c7;color:#92400e',
    ];
    $statusLabels = ['active' => 'Activo', 'on_hold' => 'En pausa', 'completed' => 'Completado', 'archived' => 'Archivado'];
    $statusColors = [
        'active'    => 'background:#d1fae5;color:#065f46',
        'on_hold'   => 'background:#fef9c3;color:#854d0e',
        'completed' => 'background:#ede9fe;color:#5b21b6',
        'archived'  => 'background:#f3f4f6;color:#6b7280',
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @forelse($this->getProjects() as $project)
    @php
        $total    = $project->tasks_count;
        $done     = $project->done_tasks_count ?? 0;
        $progress = $total > 0 ? round($done / $total * 100) : 0;
        $boardRoute = $this->getBoardRoute($project->methodology);

        // Team: owner + members, max 5 shown
        $team    = collect([$project->owner])->merge($project->members)->unique('id');
        $shown   = $team->take(5);
        $overflow = max(0, $team->count() - 5);
    @endphp
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">

        {{-- Color strip --}}
        <div class="h-1.5" style="background-color: {{ $project->color ?? '#7c3aed' }}"></div>

        <div class="p-5 flex flex-col flex-1">

            {{-- Header: name + badges --}}
            <div class="flex items-start justify-between gap-3 mb-2">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-snug">
                    {{ $project->name }}
                </h3>
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                          style="{{ $methodologyColors[$project->methodology] ?? 'background:#f3f4f6;color:#374151' }}">
                        {{ $methodologyLabels[$project->methodology] ?? ucfirst($project->methodology) }}
                    </span>
                    @if($project->status)
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                          style="{{ $statusColors[$project->status] ?? 'background:#f3f4f6;color:#6b7280' }}">
                        {{ $statusLabels[$project->status] ?? ucfirst($project->status) }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            @if($project->description)
            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-4">{{ $project->description }}</p>
            @else
            <p class="text-sm text-gray-300 dark:text-gray-600 italic mb-4">Sin descripción</p>
            @endif

            {{-- Progress bar --}}
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Progreso</span>
                    <span class="text-xs font-semibold" style="color:#7c3aed">{{ $progress }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                         style="width:{{ $progress }}%; background-color:#7c3aed"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">{{ $done }} / {{ $total }} tareas completadas</p>
            </div>

            {{-- Spacer --}}
            <div class="flex-1"></div>

            {{-- Team avatars + open button --}}
            <div class="flex items-center justify-between">

                {{-- Avatars --}}
                <div class="flex items-center">
                    @foreach($shown as $i => $member)
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}&size=28&background=7c3aed&color=fff&bold=true"
                         title="{{ $member->name }}"
                         class="h-7 w-7 rounded-full object-cover"
                         style="border:2px solid white;{{ $i > 0 ? 'margin-left:-8px' : '' }}"/>
                    @endforeach
                    @if($overflow > 0)
                    <span class="h-7 w-7 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center text-[10px] font-semibold text-gray-500 bg-gray-100 dark:bg-gray-700"
                          style="margin-left:-8px">
                        +{{ $overflow }}
                    </span>
                    @endif
                </div>

                {{-- Board link --}}
                <a href="{{ route($boardRoute) }}"
                   class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-opacity hover:opacity-90"
                   style="background-color:#7c3aed">
                    Abrir tablero
                    <x-heroicon-o-arrow-right class="h-3.5 w-3.5"/>
                </a>
            </div>

            {{-- Footer stats --}}
            <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400">
                @if($project->activeSprint)
                    <span class="flex items-center gap-1">
                        <x-heroicon-o-bolt class="h-3.5 w-3.5 text-emerald-500"/>
                        <span class="text-emerald-600 font-medium">{{ $project->activeSprint->name }}</span>
                    </span>
                @endif
                @if($project->end_date)
                    <span class="flex items-center gap-1">
                        <x-heroicon-o-calendar class="h-3.5 w-3.5"/>
                        {{ $project->end_date->format('d M Y') }}
                    </span>
                @endif
                <span class="flex items-center gap-1 ml-auto">
                    <x-heroicon-o-rectangle-stack class="h-3.5 w-3.5"/>
                    {{ $total }} tareas
                </span>
            </div>

        </div>
    </div>
    @empty
    <div class="col-span-3 text-center py-20 text-gray-400">
        <x-heroicon-o-folder-open class="w-14 h-14 mx-auto mb-4 opacity-40"/>
        <p class="text-lg font-medium text-gray-500">No tienes proyectos todavía</p>
        <p class="text-sm mt-1">Crea tu primer proyecto desde el panel de administración</p>
    </div>
    @endforelse
</div>

</x-filament-panels::page>
