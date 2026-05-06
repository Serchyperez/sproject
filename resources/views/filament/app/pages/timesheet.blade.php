<x-filament-panels::page>
    @php
        $rows         = $this->getRows();
        $days         = $this->daysInMonth();
        $dayTotals    = $this->getDayTotals();
        $totalRows    = count($rows);
        $cellData     = array_values(array_map(fn($row) => $row['days'], $rows));
        $anyRowClosed = collect($rows)->contains('closed', true);
    @endphp

    {{-- Month navigation + download buttons --}}
    <div class="mb-4 flex items-center gap-3">
        <button wire:click="previousMonth"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-heroicon-o-chevron-left class="w-4 h-4"/>
        </button>
        <span class="font-semibold text-sm text-gray-700 dark:text-gray-200 capitalize min-w-[8rem] text-center flex items-center gap-1.5">
            {{ $this->getMonthLabel() }}
            @if ($anyRowClosed)
                <x-heroicon-s-lock-closed class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 flex-shrink-0"
                    title="Mes cerrado"/>
            @endif
        </span>
        <button wire:click="nextMonth"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-heroicon-o-chevron-right class="w-4 h-4"/>
        </button>

        @if ($totalRows > 0)
            <div class="ml-auto flex items-center gap-2">
                <button wire:click="downloadCsv"
                        wire:loading.attr="disabled"
                        wire:target="downloadCsv"
                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                               text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800
                               disabled:opacity-50 transition-colors">
                    <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5"/>
                    <span wire:loading.remove wire:target="downloadCsv">CSV</span>
                    <span wire:loading wire:target="downloadCsv">...</span>
                </button>
                <button wire:click="downloadExcel"
                        wire:loading.attr="disabled"
                        wire:target="downloadExcel"
                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-emerald-300 dark:border-emerald-700
                               text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20
                               hover:bg-emerald-100 dark:hover:bg-emerald-900/40
                               disabled:opacity-50 transition-colors">
                    <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5"/>
                    <span wire:loading.remove wire:target="downloadExcel">Excel</span>
                    <span wire:loading wire:target="downloadExcel">...</span>
                </button>
            </div>
        @endif
    </div>

    @if ($totalRows === 0)
        <div class="text-center py-16 text-gray-500">
            <x-heroicon-o-clock class="w-12 h-12 mx-auto mb-3 opacity-50"/>
            <p>No tienes tareas asignadas este mes.</p>
        </div>
    @else
        {{-- Two-panel layout: fixed left labels + scrollable day grid.
             wire:key forces Alpine to reinitialize cells on month change. --}}
        <div class="flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
             wire:key="timesheet-{{ $year }}-{{ $month }}"
             x-data="{
                cells: {{ \Illuminate\Support\Js::from($cellData) }},
                fmt(v) {
                    const n = parseFloat(v) || 0;
                    return n === 0 ? '' : (n % 1 === 0 ? n.toString() : n.toFixed(2).replace(/\.?0+$/, ''));
                },
                rowTotal(rowIdx) {
                    const v = Object.values(this.cells[rowIdx] || {})
                        .reduce((s, x) => s + (parseFloat(x) || 0), 0);
                    return this.fmt(v) || '0';
                },
                dayTotal(day) {
                    const v = this.cells.reduce((s, row) => s + (parseFloat(row[day]) || 0), 0);
                    return this.fmt(v) || '';
                },
                grandTotal() {
                    const v = this.cells.reduce((s, row) =>
                        s + Object.values(row).reduce((rs, x) => rs + (parseFloat(x) || 0), 0), 0);
                    return this.fmt(v) || '0';
                },
                onBlur(rowIdx, taskId, day, date, text) {
                    const hours = parseFloat(text.trim().replace(',', '.')) || 0;
                    this.cells[rowIdx][day] = hours;
                    const el = document.getElementById('cell-' + rowIdx + '-' + day);
                    if (el) el.innerText = this.fmt(hours);
                    $wire.call('saveHours', taskId, date, hours);
                },
                selectAll(el) {
                    const range = document.createRange();
                    range.selectNodeContents(el);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                },
                validateInput(event) {
                    const nav = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Tab','Enter','Home','End'];
                    if (nav.includes(event.key) || event.ctrlKey || event.metaKey) return;
                    if (/^\d$/.test(event.key)) return;
                    if ((event.key === '.' || event.key === ',') && !event.currentTarget.innerText.includes('.') && !event.currentTarget.innerText.includes(',')) return;
                    event.preventDefault();
                },
                focusCell(rowIdx, day) {
                    if (rowIdx < 0 || rowIdx >= {{ $totalRows }} || day < 1 || day > {{ $days }}) return;
                    document.getElementById('cell-' + rowIdx + '-' + day)?.focus();
                }
             }">

            {{-- ── Left panel: Proyecto / Tarea / Subtarea (fixed, never scrolls) ── --}}
            <div class="flex-shrink-0 border-r border-gray-200 dark:border-gray-700">
                <table class="table-fixed border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="h-9 w-32 px-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Proyecto</th>
                            <th class="h-9 w-44 px-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Tarea</th>
                            <th class="h-9 w-32 px-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Subtarea</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($rows as $row)
                            @php $task = $row['task']; @endphp
                            <tr>
                                <td class="h-8 w-32 px-3 text-xs text-gray-700 dark:text-gray-300 truncate max-w-[8rem]">
                                    {{ $task->project->name }}
                                </td>
                                <td class="h-8 w-44 px-3 text-xs text-gray-700 dark:text-gray-300 truncate max-w-[11rem]">
                                    {{ $task->parent ? $task->parent->title : $task->title }}
                                </td>
                                <td class="h-8 w-32 px-3 text-xs text-gray-500 dark:text-gray-400 truncate max-w-[8rem]">
                                    {{ $task->parent ? $task->title : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60">
                            <td class="h-9 px-3 text-xs font-semibold text-gray-600 dark:text-gray-300" colspan="3">Total día</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- ── Right panel: day grid + Total column (horizontally scrollable) ── --}}
            <div class="overflow-x-auto flex-1">
                <table class="table-fixed border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                            @for ($d = 1; $d <= $days; $d++)
                                <th class="h-9 w-10 text-center text-xs font-semibold
                                    {{ $this->isWeekend($d) ? 'bg-gray-100 dark:bg-gray-700/60 text-gray-400 dark:text-gray-500' : 'text-gray-600 dark:text-gray-300' }}
                                    {{ $this->isToday($d) ? 'ring-2 ring-inset ring-violet-400 dark:ring-violet-600 text-violet-600 dark:text-violet-400' : '' }}">
                                    {{ $d }}
                                </th>
                            @endfor
                            <th class="h-9 w-14 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 border-l border-gray-200 dark:border-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($rows as $rowIdx => $row)
                            @php $task = $row['task']; $closed = $row['closed']; @endphp
                            <tr>
                                @for ($d = 1; $d <= $days; $d++)
                                    @php $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d); @endphp
                                    <td class="p-0 w-10 h-8
                                        {{ $this->isWeekend($d) ? 'bg-gray-50 dark:bg-gray-800/40' : '' }}
                                        {{ $this->isToday($d) ? 'ring-2 ring-inset ring-violet-200 dark:ring-violet-800/60' : '' }}">
                                        @if ($closed)
                                            <div class="w-10 h-8 leading-8 text-center text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800">
                                                {{ $row['days'][$d] ?: '' }}
                                            </div>
                                        @else
                                            <div
                                                id="cell-{{ $rowIdx }}-{{ $d }}"
                                                contenteditable="true"
                                                tabindex="0"
                                                @focus="selectAll($el)"
                                                @blur="onBlur({{ $rowIdx }}, {{ $task->id }}, {{ $d }}, '{{ $dateStr }}', $el.innerText)"
                                                @keydown.tab.prevent="$event.shiftKey ? focusCell({{ $rowIdx }}, {{ $d - 1 }}) : focusCell({{ $rowIdx }}, {{ $d + 1 }})"
                                                @keydown.enter.prevent="focusCell({{ $rowIdx + 1 }}, {{ $d }})"
                                                @keydown.arrow-up.prevent="focusCell({{ $rowIdx - 1 }}, {{ $d }})"
                                                @keydown.arrow-down.prevent="focusCell({{ $rowIdx + 1 }}, {{ $d }})"
                                                @keydown.arrow-left.prevent="focusCell({{ $rowIdx }}, {{ $d - 1 }})"
                                                @keydown.arrow-right.prevent="focusCell({{ $rowIdx }}, {{ $d + 1 }})"
                                                @keydown="validateInput($event)"
                                                class="w-10 h-8 leading-8 text-center text-xs
                                                       focus:bg-violet-50 dark:focus:bg-violet-900/20
                                                       focus:ring-1 focus:ring-inset focus:ring-violet-400 dark:focus:ring-violet-500
                                                       focus:outline-none cursor-default focus:cursor-text"
                                            >{{ $row['days'][$d] ?: '' }}</div>
                                        @endif
                                    </td>
                                @endfor
                                <td class="w-14 h-8 text-center text-xs font-semibold text-gray-700 dark:text-gray-200 border-l border-gray-100 dark:border-gray-800"
                                    x-text="rowTotal({{ $rowIdx }})"></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60">
                            @for ($d = 1; $d <= $days; $d++)
                                <td class="p-0 w-10 h-9 text-center text-xs font-semibold text-gray-700 dark:text-gray-200
                                    {{ $this->isWeekend($d) ? 'bg-gray-100 dark:bg-gray-700/60' : '' }}"
                                    x-text="dayTotal({{ $d }})"></td>
                            @endfor
                            <td class="w-14 h-9 text-center text-xs font-bold text-violet-600 dark:text-violet-400 border-l border-gray-200 dark:border-gray-600"
                                x-text="grandTotal()"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    @endif

    {{-- Month closing section (visible only to PMs and super_admin) --}}
    @php $closingSection = $this->getClosingSection(); @endphp
    @if (count($closingSection) > 0)
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                <x-heroicon-o-lock-closed class="w-4 h-4"/>
                Cierre de mes — {{ $this->getMonthLabel() }}
            </h3>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Proyecto</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Cerrado por</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($closingSection as $item)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ $item['project']->name }}
                                </td>
                                <td class="px-4 py-2.5">
                                    @if ($item['isClosed'])
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                                            <x-heroicon-s-lock-closed class="w-3.5 h-3.5"/>
                                            Cerrado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                            <x-heroicon-s-lock-open class="w-3.5 h-3.5"/>
                                            Abierto
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item['closedBy'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $item['closedAt'] ?? '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    @if ($item['canClose'])
                                        <button wire:click="closeMonth({{ $item['project']->id }})"
                                                wire:confirm="¿Cerrar el mes de {{ $item['project']->name }}? Las imputaciones quedarán bloqueadas."
                                                class="text-xs px-3 py-1 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800 transition-colors">
                                            Cerrar mes
                                        </button>
                                    @elseif ($item['canReopen'])
                                        <button wire:click="reopenMonth({{ $item['project']->id }})"
                                                wire:confirm="¿Reabrir el mes de {{ $item['project']->name }}?"
                                                class="text-xs px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800 transition-colors">
                                            Reabrir
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
