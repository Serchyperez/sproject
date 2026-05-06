<?php

namespace App\Filament\App\Pages;

use App\Models\MonthClosing;
use App\Models\Task;
use App\Models\TaskImputation;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Renderless;

class TimesheetView extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Imputaciones';
    protected static string $view = 'filament.app.pages.timesheet';
    protected static ?int $navigationSort = 5;

    public int $year;
    public int $month;

    public function mount(): void
    {
        $this->year  = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $d = Carbon::create($this->year, $this->month)->subMonth();
        $this->year  = $d->year;
        $this->month = $d->month;
    }

    public function nextMonth(): void
    {
        $d = Carbon::create($this->year, $this->month)->addMonth();
        $this->year  = $d->year;
        $this->month = $d->month;
    }

    public function daysInMonth(): int
    {
        return Carbon::create($this->year, $this->month)->daysInMonth;
    }

    /** All tasks assigned to the current user (including subtasks). */
    public function getRows(): array
    {
        $tasks = Task::with(['project', 'parent'])
            ->where('assigned_to', auth()->id())
            ->get()
            ->sortBy(fn ($t) => $t->project->name . $t->title);

        $days = $this->daysInMonth();

        // Index imputations: taskId → [day → hours]
        $imps = TaskImputation::where('user_id', auth()->id())
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->get()
            ->groupBy('task_id')
            ->map(fn ($g) => $g->keyBy(fn ($i) => (int) $i->date->format('j')));

        $rows = [];
        foreach ($tasks as $task) {
            $taskImps = $imps->get($task->id, collect());
            $dayHours = [];
            for ($d = 1; $d <= $days; $d++) {
                $dayHours[$d] = (float) ($taskImps->get($d)?->hours ?? 0);
            }
            $rows[] = [
                'task'     => $task,
                'days'     => $dayHours,
                'rowTotal' => array_sum($dayHours),
                'closed'   => MonthClosing::isClosed($task->project_id, $this->year, $this->month),
            ];
        }

        return $rows;
    }

    /** Column totals (sum per day across all tasks). */
    public function getDayTotals(): array
    {
        $raw = TaskImputation::where('user_id', auth()->id())
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->selectRaw('DAY(date) as d, SUM(hours) as t')
            ->groupBy('d')
            ->pluck('t', 'd');

        $totals = [];
        for ($d = 1; $d <= $this->daysInMonth(); $d++) {
            $totals[$d] = (float) ($raw->get($d) ?? 0);
        }
        return $totals;
    }

    public function getMonthLabel(): string
    {
        return Carbon::create($this->year, $this->month)
            ->locale('es')
            ->isoFormat('MMMM YYYY');
    }

    public function isWeekend(int $day): bool
    {
        return Carbon::create($this->year, $this->month, $day)->isWeekend();
    }

    public function isToday(int $day): bool
    {
        return $this->year === now()->year
            && $this->month === now()->month
            && $day === now()->day;
    }

    #[Renderless]
    public function saveHours(int $taskId, string $date, float $hours): void
    {
        if ($hours <= 0) {
            TaskImputation::where('task_id', $taskId)
                ->where('user_id', auth()->id())
                ->whereDate('date', $date)
                ->delete();
            return;
        }

        TaskImputation::updateOrCreate(
            ['task_id' => $taskId, 'user_id' => auth()->id(), 'date' => $date],
            ['hours' => $hours]
        );
    }
}
