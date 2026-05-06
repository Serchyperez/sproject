<x-filament-panels::page>
@php
    $projects = $this->getProjects();
    $members  = $this->getMembers();
    $pending  = $this->getPendingInvitations();
    $roles    = ['manager' => 'Manager', 'developer' => 'Developer', 'observer' => 'Observer', 'client' => 'Client'];
@endphp

{{-- ── Project selector ── --}}
<div class="mb-6">
    <select wire:model.live="projectId"
            class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
        @forelse ($projects as $p)
            <option value="{{ $p->id }}">{{ $p->name }}</option>
        @empty
            <option value="">Sin proyectos</option>
        @endforelse
    </select>
</div>

@if (!$projectId)
    <div class="py-16 text-center text-gray-500">
        <x-heroicon-o-user-group class="mx-auto mb-3 h-12 w-12 opacity-50"/>
        <p>Selecciona un proyecto para gestionar su equipo.</p>
    </div>
@else

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- ── Left: members ── --}}
    <div class="xl:col-span-2 space-y-4">

        {{-- Members table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Miembros del equipo
                    <span class="ml-1.5 rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-normal text-gray-500">{{ $members->count() }}</span>
                </h2>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @foreach ($members as $member)
                <div class="flex items-center gap-3 px-4 py-3">
                    {{-- Avatar --}}
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($member->user->name) }}&size=36&background=7c3aed&color=fff"
                         alt="{{ $member->user->name }}"
                         class="h-9 w-9 flex-shrink-0 rounded-full"/>

                    {{-- Name + email --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $member->user->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $member->user->email }}</p>
                    </div>

                    {{-- Role badge / selector --}}
                    @if ($member->isOwner)
                        <span class="rounded-full bg-violet-100 dark:bg-violet-900/30 px-2.5 py-0.5 text-xs font-semibold text-violet-700 dark:text-violet-300">
                            Propietario
                        </span>
                    @else
                        <select wire:change="changeMemberRole({{ $member->user_id }}, $event.target.value)"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs focus:ring-2 focus:ring-violet-500">
                            @foreach ($roles as $val => $label)
                                <option value="{{ $val }}" {{ $member->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>

                        <button wire:click="removeMember({{ $member->user_id }})"
                                wire:confirm="¿Eliminar a {{ $member->user->name }} del proyecto?"
                                class="ml-1 rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 transition-colors">
                            <x-heroicon-o-x-mark class="h-4 w-4"/>
                        </button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Add existing member --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Añadir usuario existente</h2>
            </div>
            <div class="bg-white dark:bg-gray-900 p-4 space-y-3">
                <div class="flex gap-2">
                    <input wire:model.live.debounce.300ms="memberSearch"
                           type="text"
                           placeholder="Buscar por nombre o email…"
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                    <select wire:model="addMemberRole"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
                        @foreach ($roles as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @php $results = $this->getMemberSearchResults(); @endphp
                @if ($results->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($results as $user)
                            <div class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&size=30&background=e0e7ff&color=4f46e5"
                                     class="h-8 w-8 flex-shrink-0 rounded-full"/>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                </div>
                                <button wire:click="addMember({{ $user->id }})"
                                        class="flex-shrink-0 rounded-lg bg-violet-600 px-3 py-1 text-xs font-medium text-white hover:bg-violet-700 transition-colors">
                                    Añadir
                                </button>
                            </div>
                        @endforeach
                    </div>
                @elseif (strlen($memberSearch) >= 2)
                    <p class="text-xs text-gray-400 text-center py-2">No se encontraron usuarios que no sean ya miembros.</p>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Right: invite + pending ── --}}
    <div class="space-y-4">

        {{-- Invite by email --}}
        <div class="overflow-hidden rounded-xl border border-violet-200 dark:border-violet-800/50">
            <div class="border-b border-violet-200 dark:border-violet-800/50 bg-violet-50 dark:bg-violet-900/20 px-4 py-3">
                <h2 class="text-sm font-semibold text-violet-700 dark:text-violet-300">Invitar por email</h2>
                <p class="mt-0.5 text-xs text-violet-500 dark:text-violet-400">Envía un enlace de invitación por correo</p>
            </div>
            <div class="bg-white dark:bg-gray-900 p-4">
                <form wire:submit="sendInvitation" class="space-y-3">
                    <input wire:model="inviteEmail"
                           type="email"
                           placeholder="correo@ejemplo.com"
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500"/>
                    <select wire:model="inviteRole"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500">
                        @foreach ($roles as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-violet-600 py-2 text-sm font-medium text-white hover:bg-violet-700 transition-colors">
                        <x-heroicon-o-paper-airplane class="h-4 w-4"/>
                        Enviar invitación
                    </button>
                </form>
            </div>
        </div>

        {{-- Pending invitations --}}
        @if ($pending->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Invitaciones pendientes
                    <span class="ml-1.5 rounded-full bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 text-xs font-normal text-amber-600 dark:text-amber-400">{{ $pending->count() }}</span>
                </h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @foreach ($pending as $inv)
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">{{ $inv->email }}</p>
                        <p class="text-xs text-gray-400">
                            {{ ucfirst($inv->role) }} · expira {{ $inv->expires_at->diffForHumans() }}
                        </p>
                    </div>
                    <button wire:click="cancelInvitation({{ $inv->id }})"
                            wire:confirm="¿Cancelar la invitación a {{ $inv->email }}?"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 transition-colors"
                            title="Cancelar invitación">
                        <x-heroicon-o-x-mark class="h-4 w-4"/>
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

</div>
@endif
</x-filament-panels::page>
