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
    $methodologyIcons = [
        'kanban'    => 'heroicon-o-view-columns',
        'scrum'     => 'heroicon-o-arrow-path',
        'waterfall' => 'heroicon-o-funnel',
    ];
    $methodologyIconColors = [
        'kanban'    => '#1d4ed8',
        'scrum'     => '#6d28d9',
        'waterfall' => '#92400e',
    ];
    $projects = $this->getProjects();
    $canCreate = static::canCreate();
    $isListView = $this->viewMode === 'list';
@endphp

{{-- ── Toolbar ── --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;">

    {{-- Search --}}
    <div style="position:relative;flex:1;min-width:200px;max-width:320px;">
        <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;">
            <x-heroicon-o-magnifying-glass style="width:16px;height:16px;"/>
        </span>
        <input wire:model.live.debounce.300ms="search"
               type="text"
               placeholder="Buscar proyecto..."
               style="width:100%;padding:7px 12px 7px 34px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;outline:none;background:#fff;color:#374151;"
               class="dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"/>
    </div>

    {{-- View toggle --}}
    <div style="display:flex;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;">
        <button wire:click="$set('viewMode','cards')"
                title="Vista tarjetas"
                type="button"
                style="padding:7px 10px;border:none;cursor:pointer;display:flex;align-items:center;{{ !$isListView ? 'background-color:#7c3aed;color:#fff;' : 'background-color:#fff;color:#6b7280;' }}">
            <x-heroicon-o-squares-2x2 style="width:16px;height:16px;"/>
        </button>
        <button wire:click="$set('viewMode','list')"
                title="Vista lista"
                type="button"
                style="padding:7px 10px;border:none;cursor:pointer;display:flex;align-items:center;{{ $isListView ? 'background-color:#7c3aed;color:#fff;' : 'background-color:#fff;color:#6b7280;' }}">
            <x-heroicon-o-bars-3 style="width:16px;height:16px;"/>
        </button>
    </div>

    {{-- Create button (Admin + PM only) --}}
    @if($canCreate)
    <a href="{{ route('filament.app.resources.projects.create') }}"
       wire:navigate
       style="display:inline-flex;align-items:center;gap:6px;background-color:#7c3aed;color:#fff;padding:7px 16px;border-radius:8px;font-size:0.875rem;font-weight:600;text-decoration:none;white-space:nowrap;">
        <x-heroicon-o-plus style="width:15px;height:15px;"/>
        Nuevo proyecto
    </a>
    @endif

</div>

@if($projects->isEmpty())
<div style="text-align:center;padding:80px 0;color:#9ca3af;">
    <x-heroicon-o-folder-open style="width:56px;height:56px;margin:0 auto 16px;opacity:0.4;"/>
    <p style="font-size:1.125rem;font-weight:500;color:#6b7280;">
        {{ $this->search ? 'Sin resultados para "'.$this->search.'"' : 'No tienes proyectos todavía' }}
    </p>
    @if(!$this->search)
    <p style="font-size:0.875rem;margin-top:4px;">Crea tu primer proyecto desde el botón "Nuevo proyecto"</p>
    @endif
</div>

{{-- ── CARDS VIEW ── --}}
@elseif(!$isListView)
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @foreach($projects as $project)
    @php
        $total    = $project->tasks_count;
        $done     = $project->done_tasks_count ?? 0;
        $progress = $total > 0 ? round($done / $total * 100) : 0;
        $boardRoute = $this->getBoardRoute($project->methodology);
        $team    = collect([$project->owner])->merge($project->members)->unique('id');
        $shown   = $team->take(5);
        $overflow = max(0, $team->count() - 5);
        $mIcon   = $methodologyIcons[$project->methodology] ?? 'heroicon-o-rectangle-stack';
        $mColor  = $methodologyIconColors[$project->methodology] ?? '#6b7280';
        $mStyle  = $methodologyColors[$project->methodology] ?? 'background:#f3f4f6;color:#374151';
    @endphp
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col">

        {{-- Color strip --}}
        <div style="height:6px;background-color:{{ $project->color ?? '#7c3aed' }}"></div>

        <div style="padding:20px;display:flex;flex-direction:column;flex:1;">

            {{-- Header --}}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px;">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white" style="line-height:1.4;">
                    {{ $project->name }}
                </h3>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    {{-- Methodology badge with icon --}}
                    <span style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:2px 8px;font-size:0.75rem;font-weight:500;{{ $mStyle }}">
                        <x-dynamic-component :component="$mIcon" style="width:11px;height:11px;"/>
                        {{ $methodologyLabels[$project->methodology] ?? ucfirst($project->methodology) }}
                    </span>
                    @if($project->status)
                    <span style="border-radius:9999px;padding:2px 8px;font-size:0.75rem;font-weight:500;{{ $statusColors[$project->status] ?? 'background:#f3f4f6;color:#6b7280' }}">
                        {{ $statusLabels[$project->status] ?? ucfirst($project->status) }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            @if($project->description)
            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2" style="margin-bottom:16px;">{{ $project->description }}</p>
            @else
            <p class="text-sm text-gray-300 dark:text-gray-600" style="font-style:italic;margin-bottom:16px;">Sin descripción</p>
            @endif

            {{-- Progress bar --}}
            <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Progreso</span>
                    <span style="font-size:0.75rem;font-weight:600;color:#7c3aed;">{{ $progress }}%</span>
                </div>
                <div style="height:6px;border-radius:9999px;background-color:#f3f4f6;overflow:hidden;" class="dark:bg-gray-700">
                    <div style="height:100%;border-radius:9999px;width:{{ $progress }}%;background-color:#7c3aed;transition:width .3s;"></div>
                </div>
                <p class="text-xs text-gray-400" style="margin-top:4px;">{{ $done }} / {{ $total }} tareas completadas</p>
            </div>

            <div style="flex:1;"></div>

            {{-- Team avatars + actions --}}
            <div style="display:flex;align-items:center;justify-content:space-between;">

                {{-- Avatars --}}
                <div style="display:flex;align-items:center;">
                    @foreach($shown as $i => $member)
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}&size=28&background=7c3aed&color=fff&bold=true"
                         title="{{ $member->name }}"
                         style="width:28px;height:28px;border-radius:9999px;object-fit:cover;border:2px solid #fff;{{ $i > 0 ? 'margin-left:-8px;' : '' }}"
                         class="dark:border-gray-800"/>
                    @endforeach
                    @if($overflow > 0)
                    <span style="width:28px;height:28px;border-radius:9999px;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:600;color:#6b7280;background-color:#f3f4f6;margin-left:-8px;"
                          class="dark:border-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        +{{ $overflow }}
                    </span>
                    @endif
                </div>

                {{-- Buttons: Gantt + Open board --}}
                <div style="display:flex;align-items:center;gap:6px;">
                    @if($canCreate)
                    <a href="{{ route('filament.app.resources.projects.edit', ['record' => $project->id]) }}"
                       title="Editar proyecto"
                       style="display:inline-flex;align-items:center;padding:6px 8px;border-radius:8px;border:1px solid #d1d5db;color:#6b7280;text-decoration:none;"
                       class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:opacity-80">
                        <x-heroicon-o-pencil-square style="width:14px;height:14px;"/>
                    </a>
                    @endif
                    <a href="{{ route('filament.app.pages.waterfall-view', ['projectId' => $project->id]) }}"
                       title="Ver Gantt"
                       style="display:inline-flex;align-items:center;padding:6px 8px;border-radius:8px;border:1px solid #d1d5db;color:#6b7280;text-decoration:none;font-size:0.75rem;background:#fff;"
                       class="dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 hover:opacity-80">
                        <x-heroicon-o-chart-bar style="width:14px;height:14px;"/>
                    </a>
                    <a href="{{ route($boardRoute, ['projectId' => $project->id]) }}"
                       style="display:inline-flex;align-items:center;gap:5px;background-color:#7c3aed;color:#fff;padding:6px 12px;border-radius:8px;font-size:0.75rem;font-weight:600;text-decoration:none;">
                        Abrir
                        <x-heroicon-o-arrow-right style="width:13px;height:13px;"/>
                    </a>
                </div>
            </div>

            {{-- Footer stats + team link --}}
            <div style="display:flex;align-items:center;gap:16px;margin-top:16px;padding-top:12px;border-top:1px solid #f3f4f6;font-size:0.75rem;color:#9ca3af;" class="dark:border-gray-700">
                @if($project->activeSprint)
                    <span style="display:flex;align-items:center;gap:4px;">
                        <x-heroicon-o-bolt style="width:14px;height:14px;color:#10b981;"/>
                        <span style="color:#059669;font-weight:500;">{{ $project->activeSprint->name }}</span>
                    </span>
                @endif
                @if($project->end_date)
                    <span style="display:flex;align-items:center;gap:4px;">
                        <x-heroicon-o-calendar style="width:14px;height:14px;"/>
                        {{ $project->end_date->format('d M Y') }}
                    </span>
                @endif
                <span style="display:flex;align-items:center;gap:4px;margin-left:auto;">
                    <x-heroicon-o-rectangle-stack style="width:14px;height:14px;"/>
                    {{ $total }} tareas
                </span>
                @if($canCreate)
                <a href="{{ route('filament.app.pages.team-management', ['projectId' => $project->id]) }}"
                   style="display:flex;align-items:center;gap:3px;color:#7c3aed;text-decoration:none;font-weight:500;"
                   title="Gestionar equipo">
                    <x-heroicon-o-user-group style="width:14px;height:14px;"/>
                    Equipo
                </a>
                @endif
            </div>

        </div>
    </div>
    @endforeach
</div>

{{-- ── LIST VIEW ── --}}
@else
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
    <table style="width:100%;font-size:0.875rem;border-collapse:collapse;">
        <thead>
            <tr style="background-color:#f9fafb;border-bottom:1px solid #e5e7eb;" class="dark:bg-gray-800/60 dark:border-gray-700">
                <th style="padding:10px 16px;text-align:left;font-size:0.75rem;font-weight:500;color:#6b7280;">Proyecto</th>
                <th style="padding:10px 16px;text-align:left;font-size:0.75rem;font-weight:500;color:#6b7280;">Metodología</th>
                <th style="padding:10px 16px;text-align:left;font-size:0.75rem;font-weight:500;color:#6b7280;">Estado</th>
                <th style="padding:10px 16px;text-align:left;font-size:0.75rem;font-weight:500;color:#6b7280;min-width:140px;">Progreso</th>
                <th style="padding:10px 16px;text-align:left;font-size:0.75rem;font-weight:500;color:#6b7280;">Equipo</th>
                <th style="padding:10px 16px;text-align:left;font-size:0.75rem;font-weight:500;color:#6b7280;">Fin</th>
                <th style="padding:10px 16px;text-align:right;font-size:0.75rem;font-weight:500;color:#6b7280;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
            @php
                $total    = $project->tasks_count;
                $done     = $project->done_tasks_count ?? 0;
                $progress = $total > 0 ? round($done / $total * 100) : 0;
                $boardRoute = $this->getBoardRoute($project->methodology);
                $teamCount = collect([$project->owner])->merge($project->members)->unique('id')->count();
                $mIcon   = $methodologyIcons[$project->methodology] ?? 'heroicon-o-rectangle-stack';
                $mColor  = $methodologyIconColors[$project->methodology] ?? '#6b7280';
                $mStyle  = $methodologyColors[$project->methodology] ?? 'background:#f3f4f6;color:#374151';
            @endphp
            <tr style="border-bottom:1px solid #f3f4f6;" class="dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                {{-- Name + color dot --}}
                <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:8px;height:8px;border-radius:9999px;flex-shrink:0;background-color:{{ $project->color ?? '#7c3aed' }}"></span>
                        <span class="text-gray-900 dark:text-white" style="font-weight:500;">{{ $project->name }}</span>
                    </div>
                </td>
                {{-- Methodology --}}
                <td style="padding:12px 16px;">
                    <span style="display:inline-flex;align-items:center;gap:4px;border-radius:9999px;padding:2px 8px;font-size:0.75rem;font-weight:500;{{ $mStyle }}">
                        <x-dynamic-component :component="$mIcon" style="width:11px;height:11px;"/>
                        {{ $methodologyLabels[$project->methodology] ?? ucfirst($project->methodology) }}
                    </span>
                </td>
                {{-- Status --}}
                <td style="padding:12px 16px;">
                    @if($project->status)
                    <span style="border-radius:9999px;padding:2px 8px;font-size:0.75rem;font-weight:500;{{ $statusColors[$project->status] ?? 'background:#f3f4f6;color:#6b7280' }}">
                        {{ $statusLabels[$project->status] ?? ucfirst($project->status) }}
                    </span>
                    @endif
                </td>
                {{-- Progress mini bar --}}
                <td style="padding:12px 16px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:6px;border-radius:9999px;background-color:#f3f4f6;overflow:hidden;" class="dark:bg-gray-700">
                            <div style="height:100%;border-radius:9999px;width:{{ $progress }}%;background-color:#7c3aed;"></div>
                        </div>
                        <span style="font-size:0.75rem;color:#6b7280;width:32px;text-align:right;">{{ $progress }}%</span>
                    </div>
                </td>
                {{-- Team count --}}
                <td style="padding:12px 16px;">
                    <span style="display:flex;align-items:center;gap:4px;color:#6b7280;font-size:0.75rem;">
                        <x-heroicon-o-users style="width:14px;height:14px;"/>
                        {{ $teamCount }}
                    </span>
                </td>
                {{-- End date --}}
                <td style="padding:12px 16px;font-size:0.75rem;color:#6b7280;">
                    {{ $project->end_date?->format('d M Y') ?? '—' }}
                </td>
                {{-- Actions --}}
                <td style="padding:12px 16px;text-align:right;">
                    <div style="display:inline-flex;align-items:center;gap:6px;">
                        @if($canCreate)
                        <a href="{{ route('filament.app.resources.projects.edit', ['record' => $project->id]) }}"
                           title="Editar proyecto"
                           style="display:inline-flex;align-items:center;padding:5px 8px;border-radius:6px;border:1px solid #d1d5db;color:#6b7280;text-decoration:none;font-size:0.75rem;"
                           class="dark:border-gray-600 dark:text-gray-300 hover:opacity-80">
                            <x-heroicon-o-pencil-square style="width:14px;height:14px;"/>
                        </a>
                        @endif
                        <a href="{{ route('filament.app.pages.waterfall-view', ['projectId' => $project->id]) }}"
                           title="Ver Gantt"
                           style="display:inline-flex;align-items:center;padding:5px 8px;border-radius:6px;border:1px solid #d1d5db;color:#6b7280;text-decoration:none;font-size:0.75rem;"
                           class="dark:border-gray-600 dark:text-gray-300 hover:opacity-80">
                            <x-heroicon-o-chart-bar style="width:14px;height:14px;"/>
                        </a>
                        @if($canCreate)
                        <a href="{{ route('filament.app.pages.team-management', ['projectId' => $project->id]) }}"
                           title="Gestionar equipo"
                           style="display:inline-flex;align-items:center;padding:5px 8px;border-radius:6px;border:1px solid #d1d5db;color:#6b7280;text-decoration:none;font-size:0.75rem;"
                           class="dark:border-gray-600 dark:text-gray-300 hover:opacity-80">
                            <x-heroicon-o-user-group style="width:14px;height:14px;"/>
                        </a>
                        @endif
                        <a href="{{ route($boardRoute, ['projectId' => $project->id]) }}"
                           style="display:inline-flex;align-items:center;gap:4px;background-color:#7c3aed;color:#fff;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:600;text-decoration:none;">
                            Abrir
                            <x-heroicon-o-arrow-right style="width:12px;height:12px;"/>
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</x-filament-panels::page>
