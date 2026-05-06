<div>
@if ($isOpen && $this->task)
@php
    $task     = $this->task;
    $project  = $task->project;
    $statuses = $project->taskStatuses;
    $assignableUsers = $project->members->push($project->owner)->unique('id');

    $typeLabels     = ['task' => 'Tarea', 'bug' => 'Bug', 'story' => 'Historia', 'epic' => 'Epic'];
    $priorityLabels = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente'];
    $typeColors     = ['task' => 'bg-blue-100 text-blue-700', 'bug' => 'bg-red-100 text-red-600', 'story' => 'bg-violet-100 text-violet-700', 'epic' => 'bg-amber-100 text-amber-700'];
    $priorityColors = ['low' => 'bg-gray-100 text-gray-600', 'medium' => 'bg-blue-100 text-blue-600', 'high' => 'bg-orange-100 text-orange-600', 'urgent' => 'bg-red-100 text-red-600'];
    $subtasksDone   = $task->subtasks->filter(fn ($s) => $s->status?->is_done)->count();
@endphp

<div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 px-4 pb-10"
     style="padding-top: 5rem"
     x-data="{ tab: 'details' }"
     @keydown.escape.window="$wire.close()">

    {{-- Backdrop --}}
    <div class="fixed inset-0" wire:click="close"></div>

    {{-- Panel --}}
    <div class="relative z-10 w-full max-w-3xl rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-2xl"
         @click.stop>

        {{-- ── Header ─────────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 dark:border-gray-800 px-6 py-4">

            {{-- Type --}}
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeColors[$type] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $typeLabels[$type] ?? $type }}
            </span>

            {{-- Project --}}
            <span class="text-xs text-gray-400">{{ $project->name }}</span>

            @if ($task->parent)
                <span class="text-xs text-gray-400">› {{ Str::limit($task->parent->title, 30) }}</span>
            @endif

            <div class="ml-auto flex items-center gap-2">
                {{-- Status --}}
                <select wire:model="taskStatusId"
                        class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs focus:ring-2 focus:ring-violet-500">
                    <option value="">Sin estado</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>

                {{-- Priority --}}
                <select wire:model="priority"
                        class="rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-xs focus:ring-2 focus:ring-violet-500">
                    @foreach ($priorityLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>

                {{-- Close --}}
                <button wire:click="close"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-heroicon-o-x-mark class="h-5 w-5"/>
                </button>
            </div>
        </div>

        {{-- ── Title ──────────────────────────────────────────────── --}}
        <div class="px-6 pt-5 pb-2">
            <input wire:model="title"
                   type="text"
                   class="w-full border-0 bg-transparent text-xl font-bold text-gray-900 dark:text-white placeholder-gray-300 focus:ring-0 focus:outline-none p-0"
                   placeholder="Título de la tarea"/>
        </div>

        {{-- ── Tabs ───────────────────────────────────────────────── --}}
        <div class="flex gap-1 border-b border-gray-100 dark:border-gray-800 px-6">
            @php
                $tabs = [
                    'details'  => 'Detalles',
                    'subtasks' => 'Subtareas' . ($task->subtasks->count() ? ' ('.$task->subtasks->count().')' : ''),
                    'comments' => 'Comentarios' . ($task->comments->count() ? ' ('.$task->comments->count().')' : ''),
                    'time'     => 'Tiempo',
                ];
            @endphp
            @foreach ($tabs as $key => $label)
                <button @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-b-2 border-violet-500 text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                        class="px-3 py-2.5 text-xs font-medium transition-colors -mb-px">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ── Tab content ─────────────────────────────────────────── --}}
        <div class="overflow-y-auto px-6 py-5" style="max-height: 55vh">

            {{-- Details --}}
            <div x-show="tab === 'details'" class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                {{-- Left: description + labels --}}
                <div class="sm:col-span-2 space-y-4" style="padding-right:1.25rem">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Descripción</label>
                        <textarea wire:model="description"
                                  rows="6"
                                  placeholder="Describe la tarea…"
                                  class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                    </div>

                    @if ($task->labels->isNotEmpty())
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Etiquetas</label>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($task->labels as $label)
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                                      style="background-color: {{ $label->color }}">{{ $label->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right: meta fields --}}
                <div class="space-y-5">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Asignado a</label>
                        <select wire:model="assignedTo"
                                class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
                            <option value="">Sin asignar</option>
                            @foreach ($assignableUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Tipo</label>
                        <select wire:model="type"
                                class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
                            @foreach ($typeLabels as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Fecha inicio</label>
                        <input wire:model="startDate" type="date"
                               class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Fecha fin</label>
                        <input wire:model="dueDate" type="date"
                               class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Story pts</label>
                            <input wire:model="storyPoints" type="number" min="0" max="999"
                                   class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-500 dark:text-gray-400">Horas est.</label>
                            <input wire:model="estimatedHours" type="number" min="0" step="0.5"
                                   class="w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                        </div>
                    </div>
                    <div class="space-y-1 pt-2 text-xs text-gray-400">
                        <p>Creada: {{ $task->created_at->locale('es')->diffForHumans() }}</p>
                        @if ($task->assignee)
                            <p>Asignada a: {{ $task->assignee->name }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Subtasks --}}
            <div x-show="tab === 'subtasks'" class="space-y-2">
                @if ($task->subtasks->isEmpty())
                    <p class="py-4 text-center text-sm text-gray-400">Sin subtareas todavía</p>
                @else
                    @if ($task->subtasks->count() > 0)
                    <div class="mb-3 flex items-center gap-2">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-violet-500 transition-all"
                                 style="width: {{ $task->subtasks->count() > 0 ? round($subtasksDone / $task->subtasks->count() * 100) : 0 }}%">
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $subtasksDone }}/{{ $task->subtasks->count() }}</span>
                    </div>
                    @endif

                    @foreach ($task->subtasks as $subtask)
                    <div wire:key="sub-{{ $subtask->id }}"
                         class="flex items-center gap-3 rounded-lg border border-gray-100 dark:border-gray-800 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors group">
                        <button wire:click="toggleSubtask({{ $subtask->id }})"
                                class="flex-shrink-0 rounded {{ $subtask->status?->is_done ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600' }} hover:text-emerald-500 transition-colors">
                            @if ($subtask->status?->is_done)
                                <x-heroicon-s-check-circle class="h-5 w-5"/>
                            @else
                                <x-heroicon-o-check-circle class="h-5 w-5"/>
                            @endif
                        </button>
                        <span class="flex-1 text-sm {{ $subtask->status?->is_done ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-200' }}">
                            {{ $subtask->title }}
                        </span>
                        <button wire:click="deleteSubtask({{ $subtask->id }})"
                                wire:confirm="¿Eliminar subtarea '{{ $subtask->title }}'?"
                                class="invisible group-hover:visible rounded p-1 text-gray-300 hover:text-red-500 transition-colors">
                            <x-heroicon-o-trash class="h-3.5 w-3.5"/>
                        </button>
                    </div>
                    @endforeach
                @endif

                <form wire:submit="addSubtask" class="flex gap-2 pt-2">
                    <input wire:model="newSubtaskTitle"
                           type="text"
                           placeholder="Nueva subtarea…"
                           class="flex-1 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                    <button type="submit"
                            class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-700 transition-colors">
                        Añadir
                    </button>
                </form>
            </div>

            {{-- Comments --}}
            <div x-show="tab === 'comments'" class="space-y-4">
                @forelse ($task->comments as $comment)
                <div wire:key="com-{{ $comment->id }}" class="flex gap-3 group">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($comment->user->name) }}&size=32&background=7c3aed&color=fff"
                         class="h-8 w-8 flex-shrink-0 rounded-full mt-0.5"/>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $comment->user->name }}</span>
                            <span class="text-xs text-gray-400">{{ $comment->created_at->locale('es')->diffForHumans() }}</span>
                            @if (auth()->id() === $comment->user_id || auth()->user()->hasRole('super_admin'))
                            <button wire:click="deleteComment({{ $comment->id }})"
                                    class="invisible group-hover:visible ml-auto text-xs text-gray-300 hover:text-red-500 transition-colors">
                                Eliminar
                            </button>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $comment->body }}</p>
                    </div>
                </div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-400">Sin comentarios todavía</p>
                @endforelse

                <form wire:submit="addComment" class="flex gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&size=32&background=7c3aed&color=fff"
                         class="h-8 w-8 flex-shrink-0 rounded-full mt-0.5"/>
                    <div class="flex-1 space-y-2">
                        <textarea wire:model="newComment"
                                  rows="3"
                                  placeholder="Escribe un comentario…"
                                  class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                        <button type="submit"
                                class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-violet-700 transition-colors">
                            Comentar
                        </button>
                    </div>
                </form>
            </div>

            {{-- Time --}}
            <div x-show="tab === 'time'">
                @if ($task->imputations->isEmpty())
                    <p class="py-4 text-center text-sm text-gray-400">Sin imputaciones en esta tarea. Usa la vista de <a href="{{ route('filament.app.pages.timesheet-view') }}" wire:navigate class="text-violet-500 hover:underline">Imputaciones</a> para registrar horas.</p>
                @else
                    <div class="mb-3 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ $task->imputations->count() }} registros</span>
                        <span class="font-semibold text-violet-600 dark:text-violet-400">
                            Total: {{ number_format($task->imputations->sum('hours'), 1) }}h
                            @if ($estimatedHours) / {{ $estimatedHours }}h estimadas @endif
                        </span>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-gray-100 dark:border-gray-800">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-500">
                                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-2 text-left font-medium">Usuario</th>
                                    <th class="px-4 py-2 text-right font-medium">Horas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach ($task->imputations as $imp)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $imp->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">{{ $imp->user->name }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-100">{{ $imp->hours }}h</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>

        {{-- ── Footer ──────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-800 px-6 py-4">
            <span class="text-xs text-gray-400">#{{ $task->id }} · {{ $project->name }}</span>
            <div class="flex gap-2">
                <button wire:click="save"
                        type="button"
                        style="background-color:#7c3aed;color:#fff;padding:6px 18px;border-radius:8px;font-size:0.875rem;font-weight:500;border:none;cursor:pointer;">
                    Actualizar
                </button>
                <button wire:click="close"
                        type="button"
                        style="background-color:transparent;color:#4b5563;padding:6px 18px;border-radius:8px;font-size:0.875rem;border:1px solid #d1d5db;cursor:pointer;">
                    Cerrar
                </button>
            </div>
        </div>

    </div>
</div>
@endif
</div>
