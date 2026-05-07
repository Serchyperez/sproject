<?php

namespace App\Livewire;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskStatus;
use Filament\Notifications\Notification;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateTaskModal extends Component
{
    public bool   $show        = false;
    public ?int   $projectId   = null;
    public string $methodology = 'kanban';

    public string  $title        = '';
    public string  $priority     = 'medium';
    public string  $type         = 'task';
    public ?int    $taskStatusId = null;
    public ?int    $sprintId     = null;
    public ?int    $storyPoints  = null;
    public ?int    $parentId     = null;
    public ?int    $assignedTo   = null;
    public ?string $dueDate      = null;

    #[On('open-create-task')]
    public function open(int $projectId, string $methodology, ?int $statusId = null, ?int $sprintId = null): void
    {
        $this->projectId    = $projectId;
        $this->methodology  = $methodology;
        $this->taskStatusId = $statusId;
        $this->sprintId     = $sprintId;
        $this->title        = '';
        $this->priority     = 'medium';
        $this->type         = 'task';
        $this->storyPoints  = null;
        $this->parentId     = null;
        $this->dueDate      = null;
        $this->assignedTo   = $this->defaultAssignee($projectId);
        $this->show         = true;
    }

    private function defaultAssignee(int $projectId): ?int
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['super_admin', 'admin', 'project_manager'])) return null;
        $project = Project::find($projectId);
        return $project?->allow_self_assign ? $user->id : null;
    }

    public function save(): void
    {
        $this->validate(['title' => 'required|max:255']);

        Task::create([
            'project_id'     => $this->projectId,
            'created_by'     => auth()->id(),
            'title'          => trim($this->title),
            'priority'       => $this->priority,
            'type'           => $this->type,
            'task_status_id' => $this->taskStatusId ?: null,
            'sprint_id'      => $this->sprintId ?: null,
            'story_points'   => $this->storyPoints ?: null,
            'parent_id'      => $this->parentId ?: null,
            'assigned_to'    => $this->assignedTo ?: null,
            'due_date'       => $this->dueDate ?: null,
        ]);

        $this->show = false;
        $this->dispatch('task-created');
        Notification::make()->title('Tarea creada')->success()->send();
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function getAssignableMembers(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();
        $project = Project::with(['owner', 'members'])->find($this->projectId);
        if (!$project) return collect();
        $user = auth()->user();
        if ($user->hasAnyRole(['super_admin', 'admin', 'project_manager'])) {
            return collect([$project->owner])->merge($project->members)->unique('id')->filter();
        }
        return collect([$user]);
    }

    public function getTaskStatuses(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();
        return TaskStatus::where('project_id', $this->projectId)->orderBy('order')->get();
    }

    public function getSprints(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();
        return Sprint::where('project_id', $this->projectId)
            ->whereIn('status', ['active', 'planning'])
            ->orderByRaw("FIELD(status,'active','planning')")
            ->get();
    }

    public function getParentStories(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();
        return Task::where('project_id', $this->projectId)
            ->where('type', 'story')
            ->orderBy('title')
            ->get();
    }

    public function canAssignToOthers(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'project_manager']) ?? false;
    }

    public function canAssign(): bool
    {
        if (!$this->projectId) return false;
        if ($this->canAssignToOthers()) return true;
        $project = Project::find($this->projectId);
        return (bool) $project?->allow_self_assign;
    }

    public function render(): View
    {
        return view('livewire.create-task-modal');
    }
}
