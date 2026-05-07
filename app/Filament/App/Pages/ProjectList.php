<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ProjectList extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Mis Proyectos';
    protected static string  $view            = 'filament.app.pages.project-list';
    protected static ?int    $navigationSort  = 1;

    #[Url]
    public string $search = '';

    #[Url]
    public string $viewMode = 'cards';

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'project_manager']) ?? false;
    }

    public function getProjects()
    {
        $user = auth()->user();

        return Project::visibleTo($user)
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount([
                'tasks',
                'tasks as done_tasks_count' => fn ($q) => $q->whereHas(
                    'status', fn ($s) => $s->where('is_done', true)
                ),
            ])
            ->with(['owner', 'members', 'activeSprint'])
            ->latest()
            ->get();
    }

    public function getBoardRoute(string $methodology): string
    {
        return match ($methodology) {
            'scrum'     => 'filament.app.pages.scrum-board',
            'waterfall' => 'filament.app.pages.waterfall-view',
            default     => 'filament.app.pages.kanban-board',
        };
    }
}
