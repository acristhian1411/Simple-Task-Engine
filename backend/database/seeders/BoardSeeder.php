<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Components;
use App\Models\ListModel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $dev = User::where('email', 'dev@example.com')->first();

        $backend = Components::where('name', 'Backend')->first();
        $frontend = Components::where('name', 'Frontend')->first();
        $auth = Components::where('name', 'Servicio de autenticación')->first();

        $board = Board::updateOrCreate(
            ['title' => 'Sprint 1 - MVP'],
            ['description' => 'Tablero principal del sprint 1', 'user_id' => $admin?->id]
        );

        $todo = ListModel::updateOrCreate(
            ['board_id' => $board->id, 'title' => 'Por hacer'],
            ['order' => 1]
        );
        $doing = ListModel::updateOrCreate(
            ['board_id' => $board->id, 'title' => 'En progreso'],
            ['order' => 2]
        );
        $done = ListModel::updateOrCreate(
            ['board_id' => $board->id, 'title' => 'Hecho'],
            ['order' => 3]
        );

        $tasks = [];

        $tasks[] = Task::updateOrCreate(
            ['list_id' => $todo->id, 'title' => 'Implementar login de usuarios'],
            [
                'description' => 'Pantalla de login y validación contra el servidor de auth.',
                'status' => 'todo',
                'order' => 1,
                'component_id' => $auth?->id,
                'assigned_to' => $dev?->id,
            ]
        );

        $tasks[] = Task::updateOrCreate(
            ['list_id' => $todo->id, 'title' => 'Diseñar vista de tablero'],
            [
                'description' => 'Maquetar la vista kanban con columnas arrastrables.',
                'status' => 'todo',
                'order' => 2,
                'component_id' => $frontend?->id,
                'assigned_to' => $dev?->id,
            ]
        );

        $tasks[] = Task::updateOrCreate(
            ['list_id' => $doing->id, 'title' => 'API de tareas'],
            [
                'description' => 'CRUD de tareas con filtros y búsqueda.',
                'status' => 'in_progress',
                'order' => 1,
                'component_id' => $backend?->id,
                'assigned_to' => $dev?->id,
            ]
        );

        $tasks[] = Task::updateOrCreate(
            ['list_id' => $done->id, 'title' => 'Configurar entorno de desarrollo'],
            [
                'description' => 'Docker Compose con PostgreSQL y Laravel.',
                'status' => 'done',
                'order' => 1,
                'component_id' => $backend?->id,
                'assigned_to' => $admin?->id,
            ]
        );

        foreach ($tasks as $task) {
            $task->subtasks()->firstOrCreate(
                ['task_id' => $task->id, 'title' => 'Subtarea 1 de ' . $task->title],
                ['is_completed' => $task->status === 'done']
            );
            $task->subtasks()->firstOrCreate(
                ['task_id' => $task->id, 'title' => 'Subtarea 2 de ' . $task->title],
                ['is_completed' => false]
            );
        }

        $tasks[0]->components()->syncWithoutDetaching([$auth?->id]);
        $tasks[1]->components()->syncWithoutDetaching([$frontend?->id]);
        $tasks[2]->components()->syncWithoutDetaching([$backend?->id]);

        $tasks[1]->dependencies()->firstOrCreate(['depends_on_task_id' => $tasks[2]->id]);
    }
}
