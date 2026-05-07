@if($show)
<div wire:click.self="close"
     style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;padding:20px;">
    <div wire:click.stop
         style="background:#fff;border-radius:14px;width:100%;max-width:560px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">

        {{-- Header --}}
        <div style="padding:18px 24px 14px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:1rem;font-weight:600;color:#111827;">Nueva tarea</h2>
            <button wire:click="close" type="button"
                    style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;display:flex;">
                <x-heroicon-o-x-mark style="width:20px;height:20px;"/>
            </button>
        </div>

        {{-- Body --}}
        <div style="padding:20px 24px;max-height:65vh;overflow-y:auto;">

            {{-- Title --}}
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">
                    Título <span style="color:#ef4444;">*</span>
                </label>
                <input wire:model="title" type="text" placeholder="Título de la tarea..."
                       autofocus
                       style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;box-sizing:border-box;outline:none;"/>
                @error('title')
                    <p style="color:#ef4444;font-size:0.75rem;margin-top:4px;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Priority + Type --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Prioridad</label>
                    <select wire:model="priority"
                            style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                        <option value="urgent">🔴 Urgente</option>
                        <option value="high">🟠 Alta</option>
                        <option value="medium">🔵 Media</option>
                        <option value="low">⚪ Baja</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Tipo</label>
                    <select wire:model="type"
                            style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                        @if($methodology === 'scrum')
                        <option value="story">Historia</option>
                        @endif
                        <option value="task">Tarea</option>
                        <option value="bug">Bug</option>
                        <option value="improvement">Mejora</option>
                        <option value="feature">Feature</option>
                    </select>
                </div>
            </div>

            {{-- Kanban: Status column --}}
            @if($methodology === 'kanban')
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Columna (estado)</label>
                <select wire:model="taskStatusId"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                    <option value="">Backlog (sin estado)</option>
                    @foreach($this->getTaskStatuses() as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Scrum: Sprint + Story points + Parent story --}}
            @if($methodology === 'scrum')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Sprint</label>
                    <select wire:model="sprintId"
                            style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                        <option value="">Backlog</option>
                        @foreach($this->getSprints() as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Story Points</label>
                    <input wire:model="storyPoints" type="number" min="0" placeholder="0"
                           style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;box-sizing:border-box;"/>
                </div>
            </div>

            @if($type !== 'story')
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Historia padre (opcional)</label>
                <select wire:model="parentId"
                        style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                    <option value="">Sin historia padre</option>
                    @foreach($this->getParentStories() as $story)
                    <option value="{{ $story->id }}">{{ $story->title }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @endif

            {{-- Assignee + Due date --}}
            <div style="display:grid;grid-template-columns:{{ $this->canAssign() ? '1fr 1fr' : '1fr' }};gap:12px;margin-bottom:4px;">
                @if($this->canAssign())
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Asignar a</label>
                    <select wire:model="assignedTo"
                            style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;background:#fff;box-sizing:border-box;">
                        <option value="">Sin asignar</option>
                        @foreach($this->getAssignableMembers() as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:6px;">Fecha límite</label>
                    <input wire:model="dueDate" type="date"
                           style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#374151;box-sizing:border-box;"/>
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div style="padding:14px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:8px;">
            <button wire:click="close" type="button"
                    style="padding:7px 18px;border-radius:8px;border:1px solid #d1d5db;font-size:0.875rem;color:#374151;background:#fff;cursor:pointer;">
                Cancelar
            </button>
            <button wire:click="save" type="button"
                    style="padding:7px 18px;border-radius:8px;border:none;font-size:0.875rem;font-weight:500;background-color:#7c3aed;color:#fff;cursor:pointer;">
                Crear tarea
            </button>
        </div>

    </div>
</div>
@endif
