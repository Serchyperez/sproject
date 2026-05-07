<?php

namespace App\Filament\App\Pages;

use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class WaterfallView extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-funnel';
    protected static ?string $navigationLabel = 'Waterfall';
    protected static string  $view            = 'filament.app.pages.waterfall-view';
    protected static ?int    $navigationSort  = 5;
    protected static bool    $shouldRegisterNavigation = false;

    #[Url]
    public ?int $projectId = null;

    public string $viewMode      = 'gantt';
    public array  $activeLabels  = [];
    public string $newLabelName  = '';
    public string $newLabelColor = '#6366f1';

    public function mount(): void
    {
        if (!$this->projectId) {
            $first = Project::where('owner_id', auth()->id())
                ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
                ->first();
            $this->projectId = $first?->id;
        }
    }

    public function getProjects()
    {
        return Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
            ->orderBy('name')
            ->get();
    }

    public function updatedProjectId(): void
    {
        $this->activeLabels = [];
        $this->dispatchGanttRefresh();
    }

    public function updatedViewMode(): void
    {
        $this->dispatchGanttRefresh();
    }

    public function getLabels()
    {
        if (!$this->projectId) return collect();
        return Label::where('project_id', $this->projectId)->orderBy('name')->get();
    }

    public function toggleLabel(int $labelId): void
    {
        if (in_array($labelId, $this->activeLabels)) {
            $this->activeLabels = array_values(array_filter($this->activeLabels, fn ($id) => $id !== $labelId));
        } else {
            $this->activeLabels[] = $labelId;
        }
        $this->dispatchGanttRefresh();
    }

    public function clearLabels(): void
    {
        $this->activeLabels = [];
        $this->dispatchGanttRefresh();
    }

    public function createLabel(): void
    {
        $this->validate([
            'newLabelName'  => 'required|max:50',
            'newLabelColor' => 'required',
        ]);

        if (!$this->projectId) return;

        Label::create([
            'project_id' => $this->projectId,
            'name'       => trim($this->newLabelName),
            'color'      => $this->newLabelColor,
        ]);

        $this->newLabelName  = '';
        $this->newLabelColor = '#6366f1';

        Notification::make()->title('Etiqueta creada')->success()->send();
    }

    public function deleteLabel(int $labelId): void
    {
        Label::where('id', $labelId)->where('project_id', $this->projectId)->delete();
        $this->activeLabels = array_values(array_filter($this->activeLabels, fn ($id) => $id !== $labelId));
        $this->dispatchGanttRefresh();
        Notification::make()->title('Etiqueta eliminada')->success()->send();
    }

    public function canManageLabels(): bool
    {
        if (!$this->projectId) return false;
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || Project::where('id', $this->projectId)->where('owner_id', $user->id)->exists()
            || ProjectMember::where('project_id', $this->projectId)
                ->where('user_id', $user->id)
                ->where('role', 'manager')
                ->exists();
    }

    public function updateTaskDates(int $taskId, string $startDate, string $endDate): void
    {
        Task::where('project_id', $this->projectId)->findOrFail($taskId)
            ->update(['start_date' => $startDate, 'due_date' => $endDate]);
        $this->dispatchGanttRefresh();
    }

    public function getGanttData(): array
    {
        if (!$this->projectId) return [];

        $tasks = Task::where('project_id', $this->projectId)
            ->where(fn ($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date'))
            ->with(['labels', 'status'])
            ->get();

        if (!empty($this->activeLabels)) {
            $tasks = $tasks->filter(fn ($t) =>
                $t->labels->pluck('id')->intersect($this->activeLabels)->isNotEmpty()
            );
        }

        return $tasks->map(function (Task $task) {
            $start = ($task->start_date ?? $task->due_date)->format('Y-m-d');
            $end   = ($task->due_date ?? $task->start_date)->format('Y-m-d');

            $isMilestone = $start === $end;

            $item = [
                'id'           => 'task-' . $task->id,
                'name'         => ($isMilestone ? '♦ ' : '') . $task->title,
                'start'        => $start,
                'end'          => $end,
                'progress'     => $task->status?->is_done ? 100 : 0,
                'custom_class' => ($isMilestone ? 'gantt-milestone ' : '') . 'priority-' . $task->priority,
            ];

            if ($task->predecessor_id) {
                $item['dependencies'] = 'task-' . $task->predecessor_id;
            }

            return $item;
        })->values()->toArray();
    }

    public function getListData(): array
    {
        if (!$this->projectId) return [];

        $tasks = Task::where('project_id', $this->projectId)
            ->with(['labels', 'assignee', 'status', 'predecessor'])
            ->orderByRaw('COALESCE(start_date, due_date) IS NULL, COALESCE(start_date, due_date) ASC')
            ->get();

        if (!empty($this->activeLabels)) {
            $tasks = $tasks->filter(fn ($t) =>
                $t->labels->pluck('id')->intersect($this->activeLabels)->isNotEmpty()
            );
        }

        $groups = [];

        foreach ($this->getLabels() as $label) {
            $labelTasks = $tasks->filter(fn ($t) => $t->labels->pluck('id')->contains($label->id));
            if ($labelTasks->isNotEmpty()) {
                $groups[] = ['label' => $label, 'tasks' => $labelTasks->values()];
            }
        }

        $unlabeled = $tasks->filter(fn ($t) => $t->labels->isEmpty());
        if ($unlabeled->isNotEmpty()) {
            $groups[] = ['label' => null, 'tasks' => $unlabeled->values()];
        }

        return $groups;
    }

    private function dispatchGanttRefresh(): void
    {
        if ($this->viewMode === 'gantt') {
            $this->dispatch('gantt-refresh', tasks: $this->getGanttData());
        }
    }
}
