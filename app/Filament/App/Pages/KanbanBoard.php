<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\Task;
use Filament\Notifications\Notification;
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

    public ?array $lastMove = null;

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
        $task = Task::findOrFail($taskId);
        $this->lastMove = [
            'taskId'   => $taskId,
            'statusId' => $task->task_status_id,
            'position' => $task->position,
        ];
        $task->update([
            'task_status_id' => $statusId,
            'position'       => $position,
        ]);
        $this->dispatch('task-moved');
    }

    public function moveToBacklog(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        $this->lastMove = [
            'taskId'   => $taskId,
            'statusId' => $task->task_status_id,
            'position' => $task->position,
        ];
        $task->update(['task_status_id' => null]);
        $this->dispatch('task-moved');
    }

    public function undoLastMove(): void
    {
        if (!$this->lastMove) {
            Notification::make()
                ->title('Nada que deshacer')
                ->warning()
                ->send();
            return;
        }

        Task::findOrFail($this->lastMove['taskId'])->update([
            'task_status_id' => $this->lastMove['statusId'],
            'position'       => $this->lastMove['position'],
        ]);
        $this->lastMove = null;
        $this->dispatch('task-moved');

        Notification::make()
            ->title('Movimiento deshecho')
            ->success()
            ->send();
    }

    public function cancelDrag(): void
    {
        // No DB change — just re-render so the DOM resets to DB state
        $this->dispatch('task-moved');
    }
}
