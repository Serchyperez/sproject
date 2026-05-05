<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Project $project)
    {
        return response()->json(
            $project->sprints()->withCount('tasks')->orderBy('start_date')->get()
        );
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['project_id'] = $project->id;
        $sprint = Sprint::create($data);

        return response()->json($sprint, 201);
    }

    public function start(Sprint $sprint)
    {
        Sprint::where('project_id', $sprint->project_id)
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        $sprint->update(['status' => 'active', 'start_date' => now()]);

        return response()->json($sprint);
    }

    public function complete(Sprint $sprint)
    {
        $sprint->update(['status' => 'completed', 'end_date' => now()]);
        return response()->json($sprint);
    }
}
