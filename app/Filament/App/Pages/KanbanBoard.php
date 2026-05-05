<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\Task;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class KanbanBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Kanban';
    protected static string $view = 'filament.app.pages.kanban-board';
    protected static ?int $navigationSort = 2;

    #[Url]
    public ?int $projectId = null;

    public bool $showBacklog = false;

    public function mount(): void
    {
        if (!$this->projectId) {
            $first = Project::visibleTo(auth()->user())->first();
            $this->projectId = $first?->id;
        }
    }

    public function getProject(): ?Project
    {
        return $this->projectId
            ? Project::with(['taskStatuses.tasks' => fn ($q) => $q->with('assignee')->orderBy('position'), 'members'])->find($this->projectId)
            : null;
    }

    public function getProjects()
    {
        return Project::visibleTo(auth()->user())->get();
    }

    public function getBacklogTasks()
    {
        if (!$this->projectId) return collect();

        return Task::where('project_id', $this->projectId)
            ->whereNull('task_status_id')
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get();
    }

    public function moveTask(int $taskId, int $statusId, int $position): void
    {
        Task::findOrFail($taskId)->update([
            'task_status_id' => $statusId,
            'position'       => $position,
        ]);

        $this->dispatch('task-moved');
    }

    public function moveToBacklog(int $taskId): void
    {
        Task::findOrFail($taskId)->update(['task_status_id' => null]);
        $this->dispatch('task-moved');
    }
}
