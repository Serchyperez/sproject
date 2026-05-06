<?php

namespace App\Filament\App\Pages;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Url;

class TeamManagement extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Equipos';
    protected static string  $view            = 'filament.app.pages.team-management';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int    $navigationSort  = 11;

    #[Url]
    public ?int $projectId = null;

    public string $memberSearch  = '';
    public string $addMemberRole = 'developer';
    public string $inviteEmail   = '';
    public string $inviteRole    = 'developer';

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

    public function mount(): void
    {
        if (!$this->projectId) {
            $this->projectId = $this->getProjects()->first()?->id;
        }
    }

    public function getProjects()
    {
        $user = auth()->user();
        if ($user->hasRole('super_admin')) {
            return Project::orderBy('name')->get();
        }

        return Project::where(function ($q) use ($user) {
            $q->where('owner_id', $user->id)
              ->orWhereHas('projectMembers', fn ($m) =>
                  $m->where('user_id', $user->id)->where('role', 'manager')
              );
        })->orderBy('name')->get();
    }

    public function updatedProjectId(): void
    {
        $this->memberSearch = '';
        $this->inviteEmail  = '';
    }

    public function updatedMemberSearch(): void {}

    public function getMembers()
    {
        if (!$this->projectId) return collect();

        $project = Project::with('owner')->find($this->projectId);
        $members = ProjectMember::where('project_id', $this->projectId)
            ->with('user')
            ->get();

        // Prepend owner (not in project_members)
        $owner = $project->owner;
        $result = collect([
            (object) [
                'user'        => $owner,
                'role'        => 'owner',
                'isOwner'     => true,
                'id'          => null,
                'project_id'  => $this->projectId,
                'user_id'     => $owner->id,
            ],
        ]);

        foreach ($members as $m) {
            $result->push((object) [
                'user'       => $m->user,
                'role'       => $m->role,
                'isOwner'    => false,
                'id'         => $m->id,
                'project_id' => $m->project_id,
                'user_id'    => $m->user_id,
            ]);
        }

        return $result;
    }

    public function getMemberSearchResults()
    {
        if (strlen($this->memberSearch) < 2) return collect();

        $project = Project::find($this->projectId);
        $existingIds = ProjectMember::where('project_id', $this->projectId)->pluck('user_id');
        $existingIds->push($project->owner_id);

        return User::where(function ($q) {
            $q->where('name', 'like', '%' . $this->memberSearch . '%')
              ->orWhere('email', 'like', '%' . $this->memberSearch . '%');
        })
        ->whereNotIn('id', $existingIds)
        ->limit(6)
        ->get();
    }

    public function getPendingInvitations()
    {
        if (!$this->projectId) return collect();

        return Invitation::where('project_id', $this->projectId)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('invitedBy')
            ->latest()
            ->get();
    }

    // ── Member actions ────────────────────────────────────────────────

    public function addMember(int $userId): void
    {
        if (!$this->projectId) return;

        ProjectMember::firstOrCreate(
            ['project_id' => $this->projectId, 'user_id' => $userId],
            ['role' => $this->addMemberRole]
        );

        $this->memberSearch = '';
        Notification::make()->title('Miembro añadido al proyecto')->success()->send();
    }

    public function removeMember(int $userId): void
    {
        ProjectMember::where('project_id', $this->projectId)
            ->where('user_id', $userId)
            ->delete();

        Notification::make()->title('Miembro eliminado del proyecto')->success()->send();
    }

    public function changeMemberRole(int $userId, string $role): void
    {
        ProjectMember::where('project_id', $this->projectId)
            ->where('user_id', $userId)
            ->update(['role' => $role]);
    }

    // ── Invitation actions ────────────────────────────────────────────

    public function sendInvitation(): void
    {
        $this->validate([
            'inviteEmail' => 'required|email',
            'inviteRole'  => 'required|in:manager,developer,observer,client',
        ]);

        if (!$this->projectId) return;

        // Check if already a member
        $project = Project::with('owner')->find($this->projectId);
        $isAlreadyMember = $project->owner->email === $this->inviteEmail
            || ProjectMember::where('project_id', $this->projectId)
                ->whereHas('user', fn ($q) => $q->where('email', $this->inviteEmail))
                ->exists();

        if ($isAlreadyMember) {
            Notification::make()->title('Este usuario ya es miembro del proyecto')->warning()->send();
            return;
        }

        // Cancel any previous pending invitation for same email+project
        Invitation::where('project_id', $this->projectId)
            ->where('email', $this->inviteEmail)
            ->whereNull('accepted_at')
            ->delete();

        $invitation = Invitation::create([
            'email'      => $this->inviteEmail,
            'project_id' => $this->projectId,
            'role'       => $this->inviteRole,
            'token'      => Invitation::generateToken(),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);

        $url = route('invitation.accept', $invitation->token);

        try {
            Mail::to($this->inviteEmail)->send(new InvitationMail($invitation));
            $body = null;
        } catch (\Exception) {
            $body = 'Email no enviado (revisa config. de correo). URL: ' . $url;
        }

        $this->inviteEmail = '';

        Notification::make()
            ->title('Invitación enviada')
            ->body($body ?? (config('mail.default') === 'log' ? 'URL de prueba: ' . $url : null))
            ->success()
            ->persistent($body !== null || config('mail.default') === 'log')
            ->send();
    }

    public function cancelInvitation(int $id): void
    {
        Invitation::where('id', $id)->where('project_id', $this->projectId)->delete();
        Notification::make()->title('Invitación cancelada')->success()->send();
    }
}
