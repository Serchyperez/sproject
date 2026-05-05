<?php

namespace App\Filament\App\Pages;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class WaterfallView extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationLabel = 'Waterfall';
    protected static string $view = 'filament.app.pages.waterfall-view';
    protected static ?int $navigationSort = 5;

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

    public function getPhases(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        return Milestone::where('project_id', $this->projectId)
            ->with(['tasks.assignee', 'tasks.status'])
            ->orderBy('due_date')
            ->get();
    }

    public function getUnassignedTasks(): \Illuminate\Support\Collection
    {
        if (!$this->projectId) return collect();

        return Task::where('project_id', $this->projectId)
            ->whereNull('milestone_id')
            ->with(['assignee', 'status'])
            ->orderBy('position')
            ->get();
    }
}
