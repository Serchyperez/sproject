<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($this->getProjects() as $project)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
                <div class="h-2" style="background-color: {{ $project->color }}"></div>
                <div class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $project->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $project->description }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            {{ $project->methodology === 'kanban' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $project->methodology === 'scrum' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $project->methodology === 'waterfall' ? 'bg-amber-100 text-amber-800' : '' }}
                        ">
                            {{ ucfirst($project->methodology) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-check-circle class="w-4 h-4"/>
                                {{ $project->tasks_count }} tareas
                            </span>
                            @if($project->end_date)
                                <span class="flex items-center gap-1">
                                    <x-heroicon-o-calendar class="w-4 h-4"/>
                                    {{ $project->end_date->format('d/m/Y') }}
                                </span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('filament.app.pages.kanban-board', ['projectId' => $project->id]) }}"
                               class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                Abrir →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-16 text-gray-500">
                <x-heroicon-o-folder-open class="w-12 h-12 mx-auto mb-3 opacity-50"/>
                <p class="text-lg font-medium">No tienes proyectos todavía</p>
                <p class="text-sm mt-1">Crea tu primer proyecto desde el panel de administración</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
