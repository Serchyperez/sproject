<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $query = $project->tasks()
            ->with(['status', 'assignee', 'sprint', 'milestone'])
            ->withCount('subtasks');

        if ($request->status_id) {
            $query->where('task_status_id', $request->status_id);
        }

        if ($request->sprint_id) {
            $query->where('sprint_id', $request->sprint_id);
        }

        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->parent_id === 'null') {
            $query->whereNull('parent_id');
        }

        return response()->json($query->orderBy('position')->get());
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'type' => 'sometimes|in:task,bug,story,epic',
            'task_status_id' => 'nullable|exists:task_statuses,id',
            'sprint_id' => 'nullable|exists:sprints,id',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assigned_to' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'story_points' => 'nullable|integer',
            'estimated_hours' => 'nullable|numeric',
            'due_date' => 'nullable|date',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = $request->user()->id;

        if (empty($data['task_status_id'])) {
            $defaultStatus = $project->taskStatuses()->where('is_default', true)->first();
            $data['task_status_id'] = $defaultStatus?->id;
        }

        $task = Task::create($data);
        Cache::tags(['project-' . $project->id])->flush();

        return response()->json($task->load(['status', 'assignee']), 201);
    }

    public function show(Task $task)
    {
        return response()->json(
            $task->load(['status', 'assignee', 'creator', 'sprint', 'milestone', 'subtasks.assignee', 'comments.user', 'imputations.user'])
        );
    }

    public function update(Request $request, Task $task)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'type' => 'sometimes|in:task,bug,story,epic',
            'task_status_id' => 'nullable|exists:task_statuses,id',
            'sprint_id' => 'nullable|exists:sprints,id',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assigned_to' => 'nullable|exists:users,id',
            'story_points' => 'nullable|integer',
            'estimated_hours' => 'nullable|numeric',
            'due_date' => 'nullable|date',
        ]);

        $task->update($data);
        Cache::tags(['project-' . $task->project_id])->flush();

        return response()->json($task->fresh(['status', 'assignee']));
    }

    public function destroy(Task $task)
    {
        $projectId = $task->project_id;
        $task->delete();
        Cache::tags(['project-' . $projectId])->flush();
        return response()->json(null, 204);
    }

    public function move(Request $request, Task $task)
    {
        $data = $request->validate([
            'task_status_id' => 'required|exists:task_statuses,id',
            'position' => 'sometimes|integer',
            'sprint_id' => 'nullable|exists:sprints,id',
        ]);

        $task->update($data);
        Cache::tags(['project-' . $task->project_id])->flush();

        return response()->json($task->fresh(['status']));
    }

    public function subtasks(Task $task)
    {
        return response()->json($task->subtasks()->with(['status', 'assignee'])->get());
    }

    public function storeSubtask(Request $request, Task $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $subtask = Task::create(array_merge($data, [
            'project_id' => $task->project_id,
            'parent_id' => $task->id,
            'task_status_id' => $task->task_status_id,
            'created_by' => $request->user()->id,
        ]));

        return response()->json($subtask, 201);
    }
}
