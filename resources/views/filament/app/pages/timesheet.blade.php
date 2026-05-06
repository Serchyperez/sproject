<x-filament-panels::page>
    {{-- Month navigation --}}
    <div class="mb-4 flex items-center gap-3">
        <button wire:click="previousMonth"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-heroicon-o-chevron-left class="w-4 h-4"/>
        </button>
        <span class="font-semibold text-sm text-gray-700 dark:text-gray-200 capitalize min-w-[8rem] text-center">
            {{ $this->getMonthLabel() }}
        </span>
        <button wire:click="nextMonth"
                class="p-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            <x-heroicon-o-chevron-right class="w-4 h-4"/>
        </button>
    </div>

    @php
        $rows      = $this->getRows();
        $days      = $this->daysInMonth();
        $dayTotals = $this->getDayTotals();
        $totalRows = count($rows);
        $cellData  = array_values(array_map(fn($row) => $row['days'], $rows));
    @endphp

    @if ($totalRows === 0)
        <div class="text-center py-16 text-gray-500">
            <x-heroicon-o-clock class="w-12 h-12 mx-auto mb-3 opacity-50"/>
            <p>No tienes tareas asignadas este mes.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700"
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
            <table class="border-collapse text-sm w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                        <th class="sticky left-0 z-20 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-[8rem]">Proyecto</th>
                        <th class="sticky left-32 z-20 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-[10rem]">Tarea</th>
                        <th class="sticky left-[18rem] z-20 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-[8rem] border-r border-gray-200 dark:border-gray-600">Subtarea</th>
                        @for ($d = 1; $d <= $days; $d++)
                            <th class="px-0 py-2 text-center text-xs font-semibold w-10
                                {{ $this->isWeekend($d) ? 'bg-gray-100 dark:bg-gray-700/60 text-gray-400 dark:text-gray-500' : 'text-gray-600 dark:text-gray-300' }}
                                {{ $this->isToday($d) ? 'ring-2 ring-inset ring-violet-400 dark:ring-violet-600 text-violet-600 dark:text-violet-400' : '' }}">
                                {{ $d }}
                            </th>
                        @endfor
                        <th class="px-2 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 min-w-[3.5rem] border-l border-gray-200 dark:border-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($rows as $rowIdx => $row)
                        @php $task = $row['task']; $closed = $row['closed']; @endphp
                        <tr>
                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-3 py-1 text-xs text-gray-700 dark:text-gray-300 min-w-[8rem] max-w-[8rem] truncate">
                                {{ $task->project->name }}
                            </td>
                            <td class="sticky left-32 z-10 bg-white dark:bg-gray-900 px-3 py-1 text-xs text-gray-700 dark:text-gray-300 min-w-[10rem] max-w-[10rem] truncate">
                                {{ $task->parent ? $task->parent->title : $task->title }}
                            </td>
                            <td class="sticky left-[18rem] z-10 bg-white dark:bg-gray-900 px-3 py-1 text-xs text-gray-500 dark:text-gray-400 min-w-[8rem] max-w-[8rem] truncate border-r border-gray-100 dark:border-gray-800">
                                {{ $task->parent ? $task->title : '—' }}
                            </td>
                            @for ($d = 1; $d <= $days; $d++)
                                @php $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d); @endphp
                                <td class="p-0 text-center w-10
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
                            <td class="px-2 py-1 text-center text-xs font-semibold text-gray-700 dark:text-gray-200 border-l border-gray-100 dark:border-gray-800 min-w-[3.5rem]"
                                x-text="rowTotal({{ $rowIdx }})"></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/60">
                        <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300">Total día</td>
                        <td class="sticky left-32 z-10 bg-gray-50 dark:bg-gray-800"></td>
                        <td class="sticky left-[18rem] z-10 bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-600"></td>
                        @for ($d = 1; $d <= $days; $d++)
                            <td class="p-0 w-10 text-center text-xs font-semibold text-gray-700 dark:text-gray-200
                                {{ $this->isWeekend($d) ? 'bg-gray-100 dark:bg-gray-700/60' : '' }}"
                                x-text="dayTotal({{ $d }})"></td>
                        @endfor
                        <td class="px-2 py-2 text-center text-xs font-bold text-violet-600 dark:text-violet-400 border-l border-gray-200 dark:border-gray-600"
                            x-text="grandTotal()"></td>
                    </tr>
                </tfoot>
            </table>
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
