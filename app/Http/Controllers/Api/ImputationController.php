<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskImputation;
use Illuminate\Http\Request;

class ImputationController extends Controller
{
    public function indexByTask(Task $task)
    {
        return response()->json(
            $task->imputations()->with('user')->orderBy('date', 'desc')->get()
        );
    }

    public function storeByTask(Request $request, Task $task)
    {
        $data = $request->validate([
            'hours' => 'required|numeric|min:0.25|max:24',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        $data['task_id'] = $task->id;
        $data['user_id'] = $request->user()->id;

        $imputation = TaskImputation::create($data);

        return response()->json($imputation->load('user'), 201);
    }

    public function indexByProject(Project $project)
    {
        $imputations = TaskImputation::whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->with(['task', 'user'])
            ->orderBy('date', 'desc')
            ->get();

        $total = $imputations->sum('hours');

        return response()->json([
            'total_hours' => $total,
            'imputations' => $imputations,
        ]);
    }
}
