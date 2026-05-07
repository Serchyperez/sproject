<?php

namespace App\Filament\App\Pages;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class GanttView extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Gantt';
    protected static string  $view            = 'filament.app.pages.gantt-view';
    protected static ?int    $navigationSort  = 4;
    protected static bool    $shouldRegisterNavigation = false;

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

    public function getGanttData(): array
    {
        if (!$this->projectId) return [];

        $tasks = Task::where('project_id', $this->projectId)
            ->whereNotNull('due_date')
            ->with(['assignee', 'status'])
            ->get();

        $milestones = Milestone::where('project_id', $this->projectId)
            ->whereNotNull('due_date')
            ->get();

        $data = [];

        foreach ($tasks as $task) {
            $start = $task->created_at->format('Y-m-d');
            $end = $task->due_date->format('Y-m-d');
            $data[] = [
                'id' => 'task-' . $task->id,
                'name' => $task->title,
                'start' => $start,
                'end' => $end,
                'progress' => $task->status?->is_done ? 100 : 0,
                'custom_class' => 'priority-' . $task->priority,
            ];
        }

        foreach ($milestones as $milestone) {
            $data[] = [
                'id' => 'milestone-' . $milestone->id,
                'name' => '🏁 ' . $milestone->name,
                'start' => $milestone->due_date->format('Y-m-d'),
                'end' => $milestone->due_date->format('Y-m-d'),
                'progress' => $milestone->status === 'completed' ? 100 : 0,
                'custom_class' => 'milestone',
            ];
        }

        return $data;
    }

    public function getProjects()
    {
        return Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
            ->get();
    }
}
