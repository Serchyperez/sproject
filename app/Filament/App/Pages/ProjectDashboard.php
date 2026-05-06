<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use App\Models\TaskImputation;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ProjectDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string  $view            = 'filament.app.pages.project-dashboard';
    protected static ?int    $navigationSort  = 2;

    #[Url]
    public ?int $projectId = null;

    public function mount(): void
    {
        if (!$this->projectId) {
            $this->projectId = $this->getProjects()->first()?->id;
        }
    }

    // ── Data helpers ─────────────────────────────────────────────────

    public function getProjects()
    {
        return Project::visibleTo(auth()->user())->orderBy('name')->get();
    }

    public function getProject(): ?Project
    {
        if (!$this->projectId) return null;

        return Project::visibleTo(auth()->user())
            ->with(['owner', 'members', 'activeSprint', 'taskStatuses'])
            ->find($this->projectId);
    }

    public function getStats(): array
    {
        if (!$this->projectId) return [];

        $statuses = \App\Models\TaskStatus::where('project_id', $this->projectId)->get();
        $doneIds  = $statuses->where('is_done', true)->pluck('id');

        $total   = \App\Models\Task::where('project_id', $this->projectId)->whereNull('parent_id')->count();
        $done    = \App\Models\Task::where('project_id', $this->projectId)->whereNull('parent_id')->whereIn('task_status_id', $doneIds)->count();
        $overdue = \App\Models\Task::where('project_id', $this->projectId)
            ->whereNull('parent_id')
            ->whereNotIn('task_status_id', $doneIds)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        $team = \App\Models\ProjectMember::where('project_id', $this->projectId)->count() + 1; // +1 owner

        return compact('total', 'done', 'overdue', 'team');
    }

    public function getTasksByStatus(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        $statuses = \App\Models\TaskStatus::where('project_id', $this->projectId)
            ->withCount(['tasks' => fn ($q) => $q->whereNull('parent_id')])
            ->orderBy('order')
            ->get();

        $total = max(1, $statuses->sum('tasks_count'));

        return $statuses->map(fn ($s) => [
            'name'    => $s->name,
            'color'   => $s->color,
            'count'   => $s->tasks_count,
            'pct'     => round($s->tasks_count / $total * 100),
            'is_done' => $s->is_done,
        ]);
    }

    public function getActiveSprint(): ?array
    {
        $project = $this->getProject();
        if (!$project?->activeSprint) return null;

        $sprint   = $project->activeSprint;
        $tasks    = \App\Models\Task::where('sprint_id', $sprint->id)->whereNull('parent_id')->with('status')->get();
        $doneIds  = \App\Models\TaskStatus::where('project_id', $this->projectId)->where('is_done', true)->pluck('id');
        $planned  = $tasks->sum('story_points');
        $done     = $tasks->whereIn('task_status_id', $doneIds)->sum('story_points');
        $total    = $tasks->count();
        $doneCount = $tasks->whereIn('task_status_id', $doneIds)->count();

        return [
            'sprint'     => $sprint,
            'planned'    => $planned,
            'done'       => $done,
            'total'      => $total,
            'done_count' => $doneCount,
            'pct'        => $planned > 0 ? round($done / $planned * 100) : ($total > 0 ? round($doneCount / $total * 100) : 0),
        ];
    }

    public function getUpcomingTasks(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        $doneIds = \App\Models\TaskStatus::where('project_id', $this->projectId)->where('is_done', true)->pluck('id');

        return \App\Models\Task::where('project_id', $this->projectId)
            ->whereNull('parent_id')
            ->whereNotIn('task_status_id', $doneIds)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now()->startOfDay())
            ->orderBy('due_date')
            ->with(['assignee', 'status'])
            ->limit(6)
            ->get();
    }

    public function getMyTasks(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        $doneIds = \App\Models\TaskStatus::where('project_id', $this->projectId)->where('is_done', true)->pluck('id');

        return \App\Models\Task::where('project_id', $this->projectId)
            ->where('assigned_to', auth()->id())
            ->whereNotIn('task_status_id', $doneIds)
            ->with('status')
            ->orderByRaw('COALESCE(due_date, "9999-12-31") ASC')
            ->limit(6)
            ->get();
    }

    public function getRecentImputations(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        return TaskImputation::whereHas('task', fn ($q) => $q->where('project_id', $this->projectId))
            ->with(['task', 'user'])
            ->orderBy('date', 'desc')
            ->limit(8)
            ->get();
    }
}
