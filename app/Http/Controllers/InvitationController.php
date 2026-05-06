<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)
            ->with(['project', 'invitedBy'])
            ->first();

        if (!$invitation) {
            return view('invitation.invalid', ['reason' => 'not_found']);
        }

        if ($invitation->accepted_at) {
            return view('invitation.invalid', ['reason' => 'already_accepted', 'project' => $invitation->project]);
        }

        if ($invitation->expires_at->isPast()) {
            return view('invitation.invalid', ['reason' => 'expired', 'project' => $invitation->project]);
        }

        $userExists = User::where('email', $invitation->email)->exists();

        if ($userExists) {
            $this->accept($invitation, User::where('email', $invitation->email)->first());
            return view('invitation.accepted', ['project' => $invitation->project, 'existing' => true]);
        }

        return view('invitation.accept', ['invitation' => $invitation]);
    }

    public function register(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->with(['project'])->firstOrFail();

        if ($invitation->accepted_at || $invitation->expires_at->isPast()) {
            return redirect()->route('invitation.accept', $token);
        }

        $request->validate([
            'name'                  => 'required|max:255',
            'password'              => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $invitation->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('developer');

        $this->accept($invitation, $user);

        Auth::login($user);

        return redirect('/app');
    }

    private function accept(Invitation $invitation, User $user): void
    {
        ProjectMember::firstOrCreate(
            ['project_id' => $invitation->project_id, 'user_id' => $user->id],
            ['role' => $invitation->role]
        );

        $invitation->update(['accepted_at' => now()]);
    }
}
