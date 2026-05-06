<?php

namespace App\Filament\App\Pages;

use App\Models\MonthClosing;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskImputation;
use Carbon\Carbon;
use Filament\Notifications\Notification;
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

    // ── Downloads ────────────────────────────────────────────────────

    public function downloadCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows   = $this->getRows();
        $days   = $this->daysInMonth();
        $totals = $this->getDayTotals();
        $label  = Carbon::create($this->year, $this->month)->format('Y-m');

        return response()->streamDownload(function () use ($rows, $days, $totals) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

            $header = ['Proyecto', 'Tarea', 'Subtarea'];
            for ($d = 1; $d <= $days; $d++) {
                $header[] = (string) $d;
            }
            $header[] = 'Total';
            fputcsv($out, $header);

            foreach ($rows as $row) {
                $task = $row['task'];
                $line = [
                    $task->project->name,
                    $task->parent ? $task->parent->title : $task->title,
                    $task->parent ? $task->title : '',
                ];
                for ($d = 1; $d <= $days; $d++) {
                    $line[] = $row['days'][$d] > 0 ? $row['days'][$d] : '';
                }
                $line[] = $row['rowTotal'] > 0 ? $row['rowTotal'] : '';
                fputcsv($out, $line);
            }

            $totalsLine = ['Total día', '', ''];
            for ($d = 1; $d <= $days; $d++) {
                $totalsLine[] = $totals[$d] > 0 ? $totals[$d] : '';
            }
            $totalsLine[] = array_sum($totals) > 0 ? array_sum($totals) : '';
            fputcsv($out, $totalsLine);

            fclose($out);
        }, "timesheet-{$label}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows   = $this->getRows();
        $days   = $this->daysInMonth();
        $totals = $this->getDayTotals();
        $label  = Carbon::create($this->year, $this->month)->format('Y-m');

        return response()->streamDownload(function () use ($rows, $days, $totals) {
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>\n";
            echo "<table border='1' cellspacing='0' cellpadding='4'>\n";

            echo "<thead><tr style='background:#f3f4f6;font-weight:bold'>";
            echo "<td>Proyecto</td><td>Tarea</td><td>Subtarea</td>";
            for ($d = 1; $d <= $days; $d++) {
                echo "<td style='text-align:center'>{$d}</td>";
            }
            echo "<td style='text-align:center'>Total</td>";
            echo "</tr></thead>\n<tbody>\n";

            foreach ($rows as $row) {
                $task    = $row['task'];
                $project = htmlspecialchars($task->project->name, ENT_QUOTES);
                $tarea   = htmlspecialchars($task->parent ? $task->parent->title : $task->title, ENT_QUOTES);
                $subtask = htmlspecialchars($task->parent ? $task->title : '', ENT_QUOTES);
                echo "<tr><td>{$project}</td><td>{$tarea}</td><td>{$subtask}</td>";
                for ($d = 1; $d <= $days; $d++) {
                    $h = $row['days'][$d] > 0 ? $row['days'][$d] : '';
                    echo "<td style='text-align:center'>{$h}</td>";
                }
                $rt = $row['rowTotal'] > 0 ? $row['rowTotal'] : '';
                echo "<td style='text-align:center;font-weight:bold'>{$rt}</td></tr>\n";
            }

            echo "</tbody>\n<tfoot>\n";
            echo "<tr style='background:#f3f4f6;font-weight:bold'>";
            echo "<td>Total día</td><td></td><td></td>";
            for ($d = 1; $d <= $days; $d++) {
                $t = $totals[$d] > 0 ? $totals[$d] : '';
                echo "<td style='text-align:center'>{$t}</td>";
            }
            $grand = array_sum($totals);
            echo "<td style='text-align:center;color:#7c3aed'>" . ($grand > 0 ? $grand : '') . "</td>";
            echo "</tr>\n</tfoot>\n</table>\n</body></html>";
        }, "timesheet-{$label}.xls", ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    #[Renderless]
    public function saveHours(int $taskId, string $date, float $hours): void
    {
        $task = Task::findOrFail($taskId);

        if (MonthClosing::isClosed($task->project_id, $this->year, $this->month)) {
            return;
        }

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

    // ── Month closing ────────────────────────────────────────────────

    /** Projects the current user can close or reopen for the displayed month. */
    public function getClosingSection(): array
    {
        $user        = auth()->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        if ($isSuperAdmin) {
            $projects = Project::with(['monthClosings.closedBy'])->orderBy('name')->get();
        } else {
            $projects = Project::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('projectMembers', fn ($m) =>
                      $m->where('user_id', $user->id)->where('role', 'manager')
                  );
            })->with(['monthClosings.closedBy'])->orderBy('name')->get();
        }

        if ($projects->isEmpty()) {
            return [];
        }

        return $projects->map(function (Project $project) use ($isSuperAdmin) {
            $closing = $project->monthClosings
                ->where('year', $this->year)
                ->where('month', $this->month)
                ->first();

            $isClosed = $closing?->is_closed ?? false;

            return [
                'project'   => $project,
                'isClosed'  => $isClosed,
                'closedAt'  => $closing?->closed_at?->locale('es')->isoFormat('D MMM YYYY, HH:mm'),
                'closedBy'  => $closing?->closedBy?->name,
                'canClose'  => !$isClosed,
                'canReopen' => $isSuperAdmin && $isClosed,
            ];
        })->toArray();
    }

    public function closeMonth(int $projectId): void
    {
        $user = auth()->user();

        $canClose = $user->hasRole('super_admin')
            || Project::where('id', $projectId)->where('owner_id', $user->id)->exists()
            || ProjectMember::where('project_id', $projectId)->where('user_id', $user->id)->where('role', 'manager')->exists();

        abort_unless($canClose, 403);

        MonthClosing::updateOrCreate(
            ['project_id' => $projectId, 'year' => $this->year, 'month' => $this->month],
            ['is_closed' => true, 'closed_by' => $user->id, 'closed_at' => now()]
        );

        Notification::make()->title('Mes cerrado correctamente')->success()->send();
    }

    public function reopenMonth(int $projectId): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        MonthClosing::where('project_id', $projectId)
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->update([
                'is_closed'   => false,
                'reopened_by' => auth()->id(),
                'reopened_at' => now(),
            ]);

        Notification::make()->title('Mes reabierto')->success()->send();
    }
}
