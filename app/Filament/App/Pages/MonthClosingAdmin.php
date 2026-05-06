<?php

namespace App\Filament\App\Pages;

use App\Models\MonthClosing;
use App\Models\Project;
use App\Models\ProjectMember;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MonthClosingAdmin extends Page
{
    protected static ?string $navigationIcon    = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel   = 'Cierre de mes';
    protected static string  $view              = 'filament.app.pages.month-closing-admin';
    protected static ?string $navigationGroup   = 'Administración';
    protected static ?int    $navigationSort    = 10;

    public int $displayYear;

    public function mount(): void
    {
        $this->displayYear = now()->year;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if ($user->hasRole('super_admin')) return true;

        return Project::where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('projectMembers', fn ($m) =>
                  $m->where('user_id', $user->id)->where('role', 'manager')
              );
        })->exists();
    }

    public function previousYear(): void { $this->displayYear--; }
    public function nextYear(): void     { $this->displayYear++; }

    /** Returns projects the current user can manage, with their 12-month closing status. */
    public function getRows(): array
    {
        $user         = auth()->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        if ($isSuperAdmin) {
            $projects = Project::orderBy('name')->get();
        } else {
            $projects = Project::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('projectMembers', fn ($m) =>
                      $m->where('user_id', $user->id)->where('role', 'manager')
                  );
            })->orderBy('name')->get();
        }

        $closings = MonthClosing::whereIn('project_id', $projects->pluck('id'))
            ->where('year', $this->displayYear)
            ->with('closedBy')
            ->get()
            ->groupBy('project_id');

        return $projects->map(function (Project $project) use ($closings, $isSuperAdmin) {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $record   = $closings->get($project->id, collect())->firstWhere('month', $m);
                $isClosed = $record?->is_closed ?? false;
                $months[$m] = [
                    'isClosed'  => $isClosed,
                    'closedBy'  => $record?->closedBy?->name,
                    'canClose'  => !$isClosed,
                    'canReopen' => $isSuperAdmin && $isClosed,
                ];
            }
            return ['project' => $project, 'months' => $months];
        })->toArray();
    }

    public function closeMonth(int $projectId, int $month): void
    {
        $user = auth()->user();

        $canClose = $user->hasRole('super_admin')
            || Project::where('id', $projectId)->where('owner_id', $user->id)->exists()
            || ProjectMember::where('project_id', $projectId)->where('user_id', $user->id)->where('role', 'manager')->exists();

        abort_unless($canClose, 403);

        MonthClosing::updateOrCreate(
            ['project_id' => $projectId, 'year' => $this->displayYear, 'month' => $month],
            ['is_closed' => true, 'closed_by' => $user->id, 'closed_at' => now()]
        );

        Notification::make()->title('Mes cerrado')->success()->send();
    }

    public function reopenMonth(int $projectId, int $month): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);

        MonthClosing::where('project_id', $projectId)
            ->where('year', $this->displayYear)
            ->where('month', $month)
            ->update([
                'is_closed'   => false,
                'reopened_by' => auth()->id(),
                'reopened_at' => now(),
            ]);

        Notification::make()->title('Mes reabierto')->success()->send();
    }

    public function monthLabel(int $month): string
    {
        return Carbon::create($this->displayYear, $month)
            ->locale('es')
            ->isoFormat('MMM');
    }

    public function isFutureMonth(int $month): bool
    {
        return Carbon::create($this->displayYear, $month)->isFuture();
    }
}
