<?php

namespace App\Livewire;

use App\Models\Task;
use App\Models\TaskComment;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TaskDetailModal extends Component
{
    public bool $isOpen = false;
    public ?int $taskId = null;

    // Editable fields
    public string  $title          = '';
    public string  $description    = '';
    public string  $priority       = 'medium';
    public string  $type           = 'task';
    public ?int    $taskStatusId   = null;
    public ?int    $assignedTo     = null;
    public ?string $startDate      = null;
    public ?string $dueDate        = null;
    public ?int    $storyPoints    = null;
    public ?float  $estimatedHours = null;

    // Inline forms
    public string $newComment      = '';
    public string $newSubtaskTitle = '';

    #[On('open-task-modal')]
    public function openTask(int $taskId): void
    {
        $task = Task::with(['project', 'status', 'assignee'])->find($taskId);
        if (!$task) return;

        $this->taskId        = $taskId;
        $this->title         = $task->title;
        $this->description   = $task->description ?? '';
        $this->priority      = $task->priority;
        $this->type          = $task->type;
        $this->taskStatusId  = $task->task_status_id;
        $this->assignedTo    = $task->assigned_to;
        $this->startDate     = $task->start_date?->format('Y-m-d');
        $this->dueDate       = $task->due_date?->format('Y-m-d');
        $this->storyPoints   = $task->story_points;
        $this->estimatedHours = $task->estimated_hours ? (float) $task->estimated_hours : null;
        $this->newComment    = '';
        $this->newSubtaskTitle = '';
        $this->isOpen        = true;
    }

    public function close(): void
    {
        $this->isOpen  = false;
        $this->taskId  = null;
    }

    public function save(): void
    {
        if (!$this->taskId) return;

        $this->validate([
            'title'    => 'required|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'type'     => 'required|in:task,bug,story,epic',
        ]);

        Task::findOrFail($this->taskId)->update([
            'title'          => trim($this->title),
            'description'    => $this->description ?: null,
            'priority'       => $this->priority,
            'type'           => $this->type,
            'task_status_id' => $this->taskStatusId,
            'assigned_to'    => $this->assignedTo,
            'start_date'     => $this->startDate ?: null,
            'due_date'       => $this->dueDate   ?: null,
            'story_points'   => $this->storyPoints,
            'estimated_hours'=> $this->estimatedHours,
        ]);

        unset($this->task);

        Notification::make()->title('Tarea actualizada')->success()->send();

        $this->close();
    }

    // ── Subtasks ──────────────────────────────────────────────────────

    public function addSubtask(): void
    {
        $this->validate(['newSubtaskTitle' => 'required|max:255']);

        $parent = Task::findOrFail($this->taskId);

        Task::create([
            'project_id' => $parent->project_id,
            'parent_id'  => $parent->id,
            'created_by' => auth()->id(),
            'title'      => trim($this->newSubtaskTitle),
            'type'       => 'task',
            'priority'   => 'medium',
        ]);

        $this->newSubtaskTitle = '';
        unset($this->task);
    }

    public function toggleSubtask(int $subtaskId): void
    {
        $subtask = Task::with('project.taskStatuses')->findOrFail($subtaskId);
        $doneStatus = $subtask->project->taskStatuses->firstWhere('is_done', true);
        $defaultStatus = $subtask->project->taskStatuses->firstWhere('is_default', true)
            ?? $subtask->project->taskStatuses->first();

        $isDone = $subtask->status?->is_done ?? false;
        $subtask->update([
            'task_status_id' => $isDone ? $defaultStatus?->id : $doneStatus?->id,
        ]);

        unset($this->task);
    }

    public function deleteSubtask(int $subtaskId): void
    {
        Task::where('id', $subtaskId)->where('parent_id', $this->taskId)->delete();
        unset($this->task);
    }

    // ── Comments ──────────────────────────────────────────────────────

    public function addComment(): void
    {
        $this->validate(['newComment' => 'required|max:5000']);

        TaskComment::create([
            'task_id' => $this->taskId,
            'user_id' => auth()->id(),
            'body'    => trim($this->newComment),
        ]);

        $this->newComment = '';
        unset($this->task);
    }

    public function deleteComment(int $commentId): void
    {
        $comment = TaskComment::findOrFail($commentId);

        abort_unless(
            auth()->id() === $comment->user_id || auth()->user()->hasRole('super_admin'),
            403
        );

        $comment->delete();
        unset($this->task);
    }

    // ── Computed ──────────────────────────────────────────────────────

    #[Computed]
    public function task(): ?Task
    {
        if (!$this->taskId) return null;

        return Task::with([
            'project.taskStatuses',
            'project.members',
            'project.owner',
            'status',
            'assignee',
            'parent',
            'subtasks'   => fn ($q) => $q->with('status')->orderBy('position'),
            'comments'   => fn ($q) => $q->with('user')->oldest(),
            'imputations'=> fn ($q) => $q->with('user')->orderBy('date', 'desc'),
            'labels',
        ])->find($this->taskId);
    }

    public function render()
    {
        return view('livewire.task-detail-modal');
    }
}
