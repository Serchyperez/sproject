<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImputationController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SprintController;
use App\Http\Controllers\Api\TaskController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Users (for selects)
        Route::get('/users', fn () => User::select('id', 'name', 'email', 'position')->get());

        // Projects
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::get('/projects/{project}/members', [ProjectController::class, 'members']);
        Route::post('/projects/{project}/members', [ProjectController::class, 'addMember']);
        Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);

        // Tasks
        Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::patch('/tasks/{task}/move', [TaskController::class, 'move']);
        Route::get('/tasks/{task}/subtasks', [TaskController::class, 'subtasks']);
        Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask']);

        // Milestones
        Route::get('/projects/{project}/milestones', [MilestoneController::class, 'index']);
        Route::post('/projects/{project}/milestones', [MilestoneController::class, 'store']);
        Route::put('/milestones/{milestone}', [MilestoneController::class, 'update']);

        // Sprints
        Route::get('/projects/{project}/sprints', [SprintController::class, 'index']);
        Route::post('/projects/{project}/sprints', [SprintController::class, 'store']);
        Route::patch('/sprints/{sprint}/start', [SprintController::class, 'start']);
        Route::patch('/sprints/{sprint}/complete', [SprintController::class, 'complete']);

        // Imputaciones
        Route::get('/tasks/{task}/imputations', [ImputationController::class, 'indexByTask']);
        Route::post('/tasks/{task}/imputations', [ImputationController::class, 'storeByTask']);
        Route::get('/projects/{project}/imputations', [ImputationController::class, 'indexByProject']);
    });
});
