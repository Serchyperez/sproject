<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskStatus;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class ScrumBoard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Scrum';
    protected static string  $view            = 'filament.app.pages.scrum-board';
    protected static ?int    $navigationSort  = 3;
    protected static bool    $shouldRegisterNavigation = false;

    #[Url]
    public ?int $projectId = null;

    #[Url]
    public ?int $sprintId = null;

    public bool $showBacklog = false;

    // Sprint creation
    public string $newSprintName  = '';
    public string $newSprintGoal  = '';
    public string $newSprintStart = '';
    public string $newSprintEnd   = '';

    // Story creation
    public string $newStoryTitle = '';

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
                ->where('status', 'active')->first()?->id
                ?? Sprint::where('project_id', $this->projectId)
                    ->where('status', 'planning')->latest()->first()?->id;
        }
    }

    public function getProjects()
    {
        return Project::where('methodology', 'scrum')
            ->where(fn ($q) => $q->where('owner_id', auth()->id())
                ->orWhereHas('members', fn ($q2) => $q2->where('user_id', auth()->id())))
            ->orderBy('name')
            ->get();
    }

    public function updatedProjectId(): void
    {
        $this->sprintId = Sprint::where('project_id', $this->projectId)
            ->where('status', 'active')->first()?->id;
        $this->dispatch('scrum-refreshed');
    }

    public function updatedSprintId(): void
    {
        $this->dispatch('scrum-refreshed');
    }

    public function getProject(): ?Project
    {
        return $this->projectId
            ? Project::with(['taskStatuses', 'sprints' => fn ($q) => $q->orderByRaw("FIELD(status,'active','planning','completed')")->orderBy('created_at', 'desc')])->find($this->projectId)
            : null;
    }

    public function getCurrentSprint(): ?Sprint
    {
        return $this->sprintId ? Sprint::find($this->sprintId) : null;
    }

    /** Tasks in the active sprint, grouped by task_status_id. */
    public function getSprintTasks(): \Illuminate\Support\Collection
    {
        if (!$this->sprintId) return collect();

        return Task::where('sprint_id', $this->sprintId)
            ->with(['status', 'assignee', 'parent'])
            ->orderBy('position')
            ->get()
            ->groupBy('task_status_id');
    }

    public function getSprintStats(): array
    {
        if (!$this->sprintId) return ['planned' => 0, 'done' => 0, 'count' => 0, 'done_count' => 0];

        $tasks = Task::where('sprint_id', $this->sprintId)->with('status')->get();
        $done  = $tasks->filter(fn ($t) => $t->status?->is_done);

        return [
            'planned'    => (int) $tasks->sum('story_points'),
            'done'       => (int) $done->sum('story_points'),
            'count'      => $tasks->count(),
            'done_count' => $done->count(),
        ];
    }

    /** Backlog: stories (with subtasks) + standalone tasks not in any sprint. */
    public function getBacklogItems(): array
    {
        if (!$this->projectId) return ['stories' => collect(), 'tasks' => collect()];

        $stories = Task::where('project_id', $this->projectId)
            ->where('type', 'story')
            ->whereNull('sprint_id')
            ->whereNull('parent_id')
            ->with(['subtasks' => fn ($q) => $q->with(['assignee', 'status'])->orderBy('position'), 'assignee', 'status'])
            ->orderBy('position')
            ->get();

        $tasks = Task::where('project_id', $this->projectId)
            ->whereNull('sprint_id')
            ->whereNull('parent_id')
            ->where('type', '!=', 'story')
            ->with(['assignee', 'status'])
            ->orderBy('position')
            ->get();

        return ['stories' => $stories, 'tasks' => $tasks];
    }

    // ── Access ────────────────────────────────────────────────────────

    public function canManageSprint(): bool
    {
        if (!$this->projectId) return false;
        $user = auth()->user();
        return $user->hasRole('super_admin')
            || Project::where('id', $this->projectId)->where('owner_id', $user->id)->exists()
            || ProjectMember::where('project_id', $this->projectId)
                ->where('user_id', $user->id)->where('role', 'manager')->exists();
    }

    // ── Sprint CRUD ───────────────────────────────────────────────────

    public function createSprint(): void
    {
        $this->validate(['newSprintName' => 'required|max:100']);

        $sprint = Sprint::create([
            'project_id' => $this->projectId,
            'name'       => trim($this->newSprintName),
            'goal'       => $this->newSprintGoal  ?: null,
            'status'     => 'planning',
            'start_date' => $this->newSprintStart ?: null,
            'end_date'   => $this->newSprintEnd   ?: null,
        ]);

        $this->sprintId = $sprint->id;
        $this->newSprintName = $this->newSprintGoal = $this->newSprintStart = $this->newSprintEnd = '';

        Notification::make()->title('Sprint creado')->success()->send();
        $this->dispatch('scrum-refreshed');
    }

    public function startSprint(): void
    {
        Sprint::where('id', $this->sprintId)->where('project_id', $this->projectId)
            ->update(['status' => 'active']);
        Notification::make()->title('Sprint iniciado')->success()->send();
        $this->dispatch('scrum-refreshed');
    }

    public function completeSprint(): void
    {
        Sprint::where('id', $this->sprintId)->where('project_id', $this->projectId)
            ->update(['status' => 'completed']);

        // Unfinished tasks return to backlog
        $doneIds = TaskStatus::where('project_id', $this->projectId)
            ->where('is_done', true)->pluck('id');
        Task::where('sprint_id', $this->sprintId)
            ->whereNotIn('task_status_id', $doneIds)
            ->update(['sprint_id' => null]);

        Notification::make()->title('Sprint completado — tareas pendientes devueltas al backlog')->success()->send();
        $this->dispatch('scrum-refreshed');
    }

    // ── Story CRUD ────────────────────────────────────────────────────

    public function createStory(): void
    {
        $this->validate(['newStoryTitle' => 'required|max:255']);

        Task::create([
            'project_id' => $this->projectId,
            'created_by' => auth()->id(),
            'title'      => trim($this->newStoryTitle),
            'type'       => 'story',
            'priority'   => 'medium',
        ]);

        $this->newStoryTitle = '';
        Notification::make()->title('Historia creada')->success()->send();
    }

    // ── Board actions ─────────────────────────────────────────────────

    public function moveTask(int $taskId, int $statusId): void
    {
        Task::findOrFail($taskId)->update(['task_status_id' => $statusId]);
        $this->dispatch('scrum-refreshed');
    }

    public function addToSprint(int $taskId): void
    {
        if (!$this->sprintId) return;
        Task::findOrFail($taskId)->update(['sprint_id' => $this->sprintId]);
        $this->dispatch('scrum-refreshed');
    }

    public function removeFromSprint(int $taskId): void
    {
        Task::findOrFail($taskId)->update(['sprint_id' => null]);
        $this->dispatch('scrum-refreshed');
    }

    public function addStoryToSprint(int $storyId): void
    {
        if (!$this->sprintId) return;
        Task::findOrFail($storyId)->update(['sprint_id' => $this->sprintId]);
        Task::where('parent_id', $storyId)->update(['sprint_id' => $this->sprintId]);
        Notification::make()->title('Historia y sus tareas añadidas al sprint')->success()->send();
        $this->dispatch('scrum-refreshed');
    }

    public function removeStoryFromSprint(int $storyId): void
    {
        Task::findOrFail($storyId)->update(['sprint_id' => null]);
        Task::where('parent_id', $storyId)->update(['sprint_id' => null]);
        $this->dispatch('scrum-refreshed');
    }

    #[On('task-created')]
    public function onTaskCreated(): void
    {
        $this->dispatch('scrum-refreshed');
    }

    public function canCreateTask(): bool
    {
        if (!$this->projectId) return false;
        $user = auth()->user();
        if ($user->hasAnyRole(['super_admin', 'admin', 'project_manager'])) return true;
        $project = Project::find($this->projectId);
        return $project?->allow_self_assign
            && ($project->owner_id === $user->id
                || $project->members()->where('users.id', $user->id)->exists());
    }
}
