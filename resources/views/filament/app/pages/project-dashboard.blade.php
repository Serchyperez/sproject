<x-filament-panels::page>
@php
    $project    = $this->getProject();
    $stats      = $project ? $this->getStats() : [];
    $byStatus   = $project ? $this->getTasksByStatus() : collect();
    $sprint     = $project ? $this->getActiveSprint() : null;
    $upcoming   = $project ? $this->getUpcomingTasks() : collect();
    $myTasks    = $project ? $this->getMyTasks() : collect();
    $imputations = $project ? $this->getRecentImputations() : collect();

    $priorityDot = ['urgent' => '#ef4444', 'high' => '#f97316', 'medium' => '#3b82f6', 'low' => '#9ca3af'];
@endphp

{{-- ── Project selector ── --}}
<div class="mb-6">
    <select wire:model.live="projectId"
            class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
        @foreach($this->getProjects() as $p)
            <option value="{{ $p->id }}">{{ $p->name }}</option>
        @endforeach
    </select>
</div>

@if(!$project)
    <div class="py-20 text-center text-gray-400">
        <x-heroicon-o-home class="w-14 h-14 mx-auto mb-4 opacity-40"/>
        <p class="text-lg font-medium text-gray-500">Selecciona un proyecto para ver el dashboard</p>
    </div>
@else

{{-- ── Stat cards ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label' => 'Tareas totales',   'value' => $stats['total'],   'icon' => 'heroicon-o-rectangle-stack',   'color' => '#7c3aed', 'bg' => '#ede9fe'],
            ['label' => 'Completadas',       'value' => $stats['done'],    'icon' => 'heroicon-o-check-circle',      'color' => '#059669', 'bg' => '#d1fae5'],
            ['label' => 'Vencidas',          'value' => $stats['overdue'], 'icon' => 'heroicon-o-exclamation-circle','color' => '#dc2626', 'bg' => '#fee2e2'],
            ['label' => 'Miembros equipo',   'value' => $stats['team'],    'icon' => 'heroicon-o-users',             'color' => '#0284c7', 'bg' => '#e0f2fe'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 flex items-center gap-4">
        <div class="rounded-xl p-2.5 flex-shrink-0" style="background-color:{{ $card['bg'] }}">
            <x-dynamic-component :component="$card['icon']" class="h-5 w-5" style="color:{{ $card['color'] }}"/>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Main grid ── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Left (2/3): tasks by status + my tasks ──────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Tasks by status --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                <x-heroicon-o-chart-bar class="h-4 w-4 text-gray-400"/>
                Tareas por estado
            </h3>
            @if($byStatus->isEmpty())
                <p class="text-sm text-gray-400 text-center py-4">Sin estados configurados</p>
            @else
            <div class="space-y-3">
                @foreach($byStatus as $row)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" style="background-color:{{ $row['color'] }}"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $row['name'] }}</span>
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ $row['count'] }} ({{ $row['pct'] }}%)</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             style="width:{{ $row['pct'] }}%;background-color:{{ $row['color'] }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- My open tasks --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                <x-heroicon-o-user-circle class="h-4 w-4 text-gray-400"/>
                Mis tareas pendientes
                @if($myTasks->isNotEmpty())
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-700">{{ $myTasks->count() }}</span>
                @endif
            </h3>
            @if($myTasks->isEmpty())
                <p class="text-sm text-gray-400 text-center py-4">No tienes tareas pendientes 🎉</p>
            @else
            <div class="space-y-2">
                @foreach($myTasks as $task)
                <div class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                    <span class="h-2 w-2 rounded-full flex-shrink-0"
                          style="background-color:{{ $priorityDot[$task->priority] ?? '#9ca3af' }}"></span>
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">{{ $task->title }}</span>
                    @if($task->status)
                        <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                              style="background-color:{{ $task->status->color }}22;color:{{ $task->status->color }}">
                            {{ $task->status->name }}
                        </span>
                    @endif
                    @if($task->due_date)
                        <span class="text-xs flex-shrink-0 {{ $task->due_date->isPast() ? 'text-red-500' : 'text-gray-400' }}">
                            {{ $task->due_date->format('d/m') }}
                        </span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- Right (1/3): sprint + upcoming deadlines ─────────────────── --}}
    <div class="space-y-6">

        {{-- Active sprint --}}
        @if($sprint)
        <div class="rounded-xl border border-violet-200 dark:border-violet-800/50 bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1 flex items-center gap-2">
                <x-heroicon-o-bolt class="h-4 w-4 text-emerald-500"/>
                Sprint activo
            </h3>
            <p class="text-base font-bold text-gray-900 dark:text-white mb-3">{{ $sprint['sprint']->name }}</p>

            @if($sprint['sprint']->goal)
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 italic">{{ $sprint['sprint']->goal }}</p>
            @endif

            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>{{ $sprint['done_count'] }} / {{ $sprint['total'] }} tareas</span>
                @if($sprint['planned'] > 0)
                    <span class="font-semibold" style="color:#7c3aed">{{ $sprint['done'] }} / {{ $sprint['planned'] }} pts</span>
                @endif
            </div>
            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mb-3">
                <div class="h-full rounded-full transition-all" style="width:{{ $sprint['pct'] }}%;background-color:#7c3aed"></div>
            </div>
            <p class="text-xs text-gray-400 text-right">{{ $sprint['pct'] }}% completado</p>

            @if($sprint['sprint']->end_date)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1.5 text-xs text-gray-400">
                <x-heroicon-o-calendar class="h-3.5 w-3.5"/>
                Fin: {{ $sprint['sprint']->end_date->format('d M Y') }}
                @if($sprint['sprint']->end_date->isPast())
                    <span class="text-red-500 ml-1">· Vencido</span>
                @elseif($sprint['sprint']->end_date->diffInDays() <= 3)
                    <span class="text-amber-500 ml-1">· {{ $sprint['sprint']->end_date->diffForHumans() }}</span>
                @endif
            </div>
            @endif
        </div>
        @endif

        {{-- Upcoming deadlines --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
                <x-heroicon-o-calendar-days class="h-4 w-4 text-gray-400"/>
                Próximas entregas
            </h3>
            @if($upcoming->isEmpty())
                <p class="text-sm text-gray-400 text-center py-3">Sin entregas próximas</p>
            @else
            <div class="space-y-2.5">
                @foreach($upcoming as $task)
                @php
                    $daysLeft = now()->startOfDay()->diffInDays($task->due_date->startOfDay(), false);
                    $urgentClass = $daysLeft <= 1 ? '#ef4444' : ($daysLeft <= 3 ? '#f97316' : '#6b7280');
                @endphp
                <div class="flex items-start gap-2.5">
                    <div class="mt-1 flex-shrink-0 h-2 w-2 rounded-full" style="background-color:{{ $urgentClass }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700 dark:text-gray-300 truncate leading-snug">{{ $task->title }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs font-medium" style="color:{{ $urgentClass }}">
                                {{ $task->due_date->format('d M') }}
                            </span>
                            @if($task->assignee)
                                <span class="text-xs text-gray-400 truncate">{{ $task->assignee->name }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ── Recent imputations ── --}}
@if($imputations->isNotEmpty())
<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4 flex items-center gap-2">
        <x-heroicon-o-clock class="h-4 w-4 text-gray-400"/>
        Imputaciones recientes
    </h3>
    <div class="overflow-hidden rounded-lg border border-gray-100 dark:border-gray-700">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-500">
                    <th class="px-4 py-2.5 text-left font-medium">Tarea</th>
                    <th class="px-4 py-2.5 text-left font-medium">Usuario</th>
                    <th class="px-4 py-2.5 text-left font-medium">Fecha</th>
                    <th class="px-4 py-2.5 text-right font-medium">Horas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($imputations as $imp)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $imp->task->title }}</td>
                    <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $imp->user->name }}</td>
                    <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $imp->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold" style="color:#7c3aed">{{ $imp->hours }}h</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif {{-- /project --}}

</x-filament-panels::page>
