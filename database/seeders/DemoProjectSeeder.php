<?php

namespace Database\Seeders;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sprojects.test')->first();

        $dev = User::firstOrCreate(
            ['email' => 'dev@sprojects.test'],
            [
                'name' => 'Developer User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'position' => 'Full Stack Developer',
            ]
        );
        $dev->assignRole('developer');

        $pm = User::firstOrCreate(
            ['email' => 'pm@sprojects.test'],
            [
                'name' => 'Project Manager',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'position' => 'Project Manager',
            ]
        );
        $pm->assignRole('project_manager');

        // Demo Kanban project
        $kanbanProject = Project::firstOrCreate(
            ['slug' => 'demo-kanban'],
            [
                'owner_id' => $admin->id,
                'name' => 'Demo Kanban Project',
                'description' => 'Proyecto de demostración con metodología Kanban',
                'methodology' => 'kanban',
                'status' => 'active',
                'color' => '#6366f1',
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
            ]
        );

        ProjectMember::firstOrCreate(
            ['project_id' => $kanbanProject->id, 'user_id' => $pm->id],
            ['role' => 'manager']
        );
        ProjectMember::firstOrCreate(
            ['project_id' => $kanbanProject->id, 'user_id' => $dev->id],
            ['role' => 'developer']
        );

        $statuses = [
            ['name' => 'Backlog', 'color' => '#6b7280', 'order' => 0, 'is_default' => true],
            ['name' => 'En progreso', 'color' => '#3b82f6', 'order' => 1],
            ['name' => 'En revisión', 'color' => '#f59e0b', 'order' => 2],
            ['name' => 'Completado', 'color' => '#10b981', 'order' => 3, 'is_done' => true],
        ];

        $createdStatuses = [];
        foreach ($statuses as $statusData) {
            $status = TaskStatus::firstOrCreate(
                ['project_id' => $kanbanProject->id, 'name' => $statusData['name']],
                array_merge($statusData, ['project_id' => $kanbanProject->id])
            );
            $createdStatuses[$statusData['name']] = $status;
        }

        $milestone = Milestone::firstOrCreate(
            ['project_id' => $kanbanProject->id, 'name' => 'v1.0 Release'],
            [
                'description' => 'Primera versión del producto',
                'due_date' => now()->addMonths(2),
                'status' => 'pending',
                'color' => '#f59e0b',
            ]
        );

        $taskData = [
            ['title' => 'Configurar entorno de desarrollo', 'priority' => 'high', 'status' => 'Completado'],
            ['title' => 'Diseñar base de datos', 'priority' => 'high', 'status' => 'Completado'],
            ['title' => 'Implementar autenticación', 'priority' => 'high', 'status' => 'En progreso'],
            ['title' => 'Crear panel de administración', 'priority' => 'medium', 'status' => 'En progreso'],
            ['title' => 'Desarrollar API REST', 'priority' => 'medium', 'status' => 'Backlog'],
            ['title' => 'Implementar Kanban Board', 'priority' => 'medium', 'status' => 'Backlog'],
            ['title' => 'Vista Gantt', 'priority' => 'low', 'status' => 'Backlog'],
            ['title' => 'Tests unitarios', 'priority' => 'low', 'status' => 'Backlog'],
        ];

        foreach ($taskData as $i => $data) {
            Task::firstOrCreate(
                ['project_id' => $kanbanProject->id, 'title' => $data['title']],
                [
                    'project_id' => $kanbanProject->id,
                    'task_status_id' => $createdStatuses[$data['status']]->id,
                    'created_by' => $admin->id,
                    'assigned_to' => $dev->id,
                    'priority' => $data['priority'],
                    'type' => 'task',
                    'milestone_id' => $milestone->id,
                    'position' => $i,
                    'estimated_hours' => rand(2, 16),
                ]
            );
        }

        // Demo Scrum project
        $scrumProject = Project::firstOrCreate(
            ['slug' => 'demo-scrum'],
            [
                'owner_id' => $admin->id,
                'name' => 'Demo Scrum Project',
                'description' => 'Proyecto de demostración con metodología Scrum',
                'methodology' => 'scrum',
                'status' => 'active',
                'color' => '#8b5cf6',
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
            ]
        );

        $scrumStatuses = [
            ['name' => 'To Do', 'color' => '#6b7280', 'order' => 0, 'is_default' => true],
            ['name' => 'In Progress', 'color' => '#3b82f6', 'order' => 1],
            ['name' => 'Done', 'color' => '#10b981', 'order' => 2, 'is_done' => true],
        ];

        foreach ($scrumStatuses as $statusData) {
            TaskStatus::firstOrCreate(
                ['project_id' => $scrumProject->id, 'name' => $statusData['name']],
                array_merge($statusData, ['project_id' => $scrumProject->id])
            );
        }

        Sprint::firstOrCreate(
            ['project_id' => $scrumProject->id, 'name' => 'Sprint 1'],
            [
                'goal' => 'Funcionalidades básicas del sistema',
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->addWeeks(2),
            ]
        );
    }
}
