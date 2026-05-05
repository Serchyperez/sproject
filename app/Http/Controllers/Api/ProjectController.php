<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::where('owner_id', $request->user()->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->withCount('tasks');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->methodology) {
            $query->where('methodology', $request->methodology);
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'methodology' => 'required|in:scrum,kanban,waterfall',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['owner_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['name']);

        $project = Project::create($data);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'role' => 'manager',
        ]);

        return response()->json($project, 201);
    }

    public function show(Project $project)
    {
        return Cache::tags(['project-' . $project->id])
            ->remember('project-' . $project->id, 300, fn () =>
                $project->load(['owner', 'members', 'milestones', 'sprints', 'taskStatuses'])
            );
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'methodology' => 'sometimes|in:scrum,kanban,waterfall',
            'status' => 'sometimes|in:active,archived,completed',
            'color' => 'nullable|string|max:7',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $project->update($data);
        Cache::tags(['project-' . $project->id])->flush();

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        $project->delete();
        Cache::tags(['project-' . $project->id])->flush();
        return response()->json(null, 204);
    }

    public function members(Project $project)
    {
        return response()->json($project->members()->withPivot('role')->get());
    }

    public function addMember(Request $request, Project $project)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,developer,observer,client',
        ]);

        $member = ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $data['user_id']],
            ['role' => $data['role']]
        );

        Cache::tags(['project-' . $project->id])->flush();

        return response()->json($member, 201);
    }

    public function removeMember(Project $project, User $user)
    {
        ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->delete();

        Cache::tags(['project-' . $project->id])->flush();

        return response()->json(null, 204);
    }
}
