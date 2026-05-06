<x-filament-panels::page>
    {{-- Year navigation --}}
    <div class="mb-6 flex items-center gap-3">
        <button wire:click="previousYear"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-heroicon-o-chevron-left class="w-4 h-4"/>
        </button>
        <span class="font-semibold text-sm text-gray-700 dark:text-gray-200 min-w-[4rem] text-center">
            {{ $this->displayYear }}
        </span>
        <button wire:click="nextYear"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-heroicon-o-chevron-right class="w-4 h-4"/>
        </button>
    </div>

    @php $rows = $this->getRows(); @endphp

    @if (count($rows) === 0)
        <div class="text-center py-16 text-gray-500">
            <x-heroicon-o-calendar-days class="w-12 h-12 mx-auto mb-3 opacity-50"/>
            <p>No tienes proyectos que gestionar.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="border-collapse text-sm w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                        <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-[12rem]">
                            Proyecto
                        </th>
                        @for ($m = 1; $m <= 12; $m++)
                            <th class="px-2 py-2.5 text-center text-xs font-semibold w-20
                                {{ $this->isFutureMonth($m) ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-300' }}
                                {{ $m === now()->month && $this->displayYear === now()->year ? 'text-violet-600 dark:text-violet-400' : '' }}">
                                {{ $this->monthLabel($m) }}
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rows as $row)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-300 min-w-[12rem]">
                                {{ $row['project']->name }}
                            </td>
                            @for ($m = 1; $m <= 12; $m++)
                                @php $cell = $row['months'][$m]; $future = $this->isFutureMonth($m); @endphp
                                <td class="px-1 py-1.5 text-center w-20 {{ $future ? 'opacity-30' : '' }}">
                                    @if ($future)
                                        <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                    @elseif ($cell['isClosed'])
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400"
                                                  title="{{ $cell['closedBy'] ? 'Cerrado por ' . $cell['closedBy'] : 'Cerrado' }}">
                                                <x-heroicon-s-lock-closed class="w-3 h-3"/>
                                            </span>
                                            @if ($cell['canReopen'])
                                                <button wire:click="reopenMonth({{ $row['project']->id }}, {{ $m }})"
                                                        wire:confirm="¿Reabrir {{ $this->monthLabel($m) }} {{ $this->displayYear }} para {{ $row['project']->name }}?"
                                                        class="text-[10px] text-emerald-600 dark:text-emerald-400 hover:underline">
                                                    Reabrir
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 dark:text-emerald-400">
                                                <x-heroicon-s-lock-open class="w-3 h-3"/>
                                            </span>
                                            @if ($cell['canClose'])
                                                <button wire:click="closeMonth({{ $row['project']->id }}, {{ $m }})"
                                                        wire:confirm="¿Cerrar {{ $this->monthLabel($m) }} {{ $this->displayYear }} para {{ $row['project']->name }}?"
                                                        class="text-[10px] text-red-600 dark:text-red-400 hover:underline">
                                                    Cerrar
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
