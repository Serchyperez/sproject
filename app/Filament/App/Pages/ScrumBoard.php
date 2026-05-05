<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ScrumBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Scrum';
    protected static string $view = 'filament.app.pages.scrum-board';
    protected static ?int $navigationSort = 3;

    #[Url]
    public ?int $projectId = null;

    #[Url]
    public ?int $sprintId = null;

    public bool $showBacklog = false;

    public function mount(): void
    {
        if (!$this->projectId) {
            $first = Project::where('methodology', 'scrum')
                ->where(fn ($q) => $q->where('owner_id', auth()->id())
                    ->orWhereHas('members', fn ($q2) => $q2->where('user_id', auth()->id())))
                ->first();
            $this->projectId = $first?->id;
        }

        if ($this->projectId && !$this->sprintId) {
            $this->sprintId = Sprint::where('project_id', $this->projectId)
                ->where('status', 'active')
                ->first()?->id;
        }
    }

    public function getProject(): ?Project
    {
        return $this->projectId
            ? Project::with(['taskStatuses', 'sprints'])->find($this->projectId)
            : null;
    }

    public function getSprintTasks(): \Illuminate\Support\Collection
    {
        if (!$this->sprintId) return collect();

        return Task::where('sprint_id', $this->sprintId)
            ->with(['status', 'assignee'])
            ->orderBy('position')
            ->get()
            ->groupBy('task_status_id');
    }

    public function getBacklogTasks(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        return Task::where('project_id', $this->projectId)
            ->whereNull('sprint_id')
            ->with(['status', 'assignee'])
            ->orderBy('position')
            ->get();
    }

    public function moveTask(int $taskId, int $statusId): void
    {
        Task::findOrFail($taskId)->update(['task_status_id' => $statusId]);
    }

    public function addToSprint(int $taskId): void
    {
        Task::findOrFail($taskId)->update(['sprint_id' => $this->sprintId]);
    }

    public function removeFromSprint(int $taskId): void
    {
        Task::findOrFail($taskId)->update(['sprint_id' => null]);
    }
}
