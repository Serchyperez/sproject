<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index(Project $project)
    {
        return response()->json(
            $project->milestones()->withCount('tasks')->orderBy('due_date')->get()
        );
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'color' => 'nullable|string|max:7',
        ]);

        $data['project_id'] = $project->id;
        $milestone = Milestone::create($data);

        return response()->json($milestone, 201);
    }

    public function update(Request $request, Milestone $milestone)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:pending,in_progress,completed',
            'color' => 'nullable|string|max:7',
        ]);

        $milestone->update($data);
        return response()->json($milestone);
    }
}
