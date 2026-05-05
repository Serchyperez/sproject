<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
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

    public function mount(): void
    {
        if (!$this->projectId) {
            $first = Project::where('owner_id', auth()->id())
                ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
                ->first();
            $this->projectId = $first?->id;
        }
    }

    public function getProject(): ?Project
    {
        return $this->projectId
            ? Project::with(['taskStatuses.tasks.assignee', 'members'])->find($this->projectId)
            : null;
    }

    public function moveTask(int $taskId, int $statusId, int $position): void
    {
        $task = Task::findOrFail($taskId);
        $task->update(['task_status_id' => $statusId, 'position' => $position]);

        $this->dispatch('task-moved');
    }

    public function getProjects()
    {
        return Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
            ->get();
    }
}
