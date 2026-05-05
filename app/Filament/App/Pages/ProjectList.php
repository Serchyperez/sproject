<?php

namespace App\Filament\App\Pages;

use App\Models\Project;
use Filament\Pages\Page;

class ProjectList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Mis Proyectos';
    protected static string $view = 'filament.app.pages.project-list';
    protected static ?int $navigationSort = 1;

    public function getProjects()
    {
        return Project::where('owner_id', auth()->id())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
            ->withCount('tasks')
            ->with(['owner', 'activeSprint'])
            ->latest()
            ->get();
    }
}
