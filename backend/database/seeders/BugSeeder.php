<?php

namespace Database\Seeders;

use App\Models\Bugs;
use App\Models\Comments;
use App\Models\Recordings;
use App\Models\Task;
use App\Models\TestCases;
use App\Models\TestSteps;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BugSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $qa = User::where('email', 'qa@example.com')->first();
        $dev = User::where('email', 'dev@example.com')->first();

        $failedCase = TestCases::where('title', 'Login con contraseña incorrecta')->first();
        $step = TestSteps::where('test_case_id', $failedCase?->id)->first();

        $bug1 = Bugs::updateOrCreate(
            ['title' => 'El error de login no se muestra en pantalla'],
            [
                'description' => 'Al ingresar una contraseña incorrecta la app redirige sin mostrar mensaje.',
                'severity' => 'high',
                'status' => 'open',
                'test_case_id' => $failedCase?->id,
                'test_step_id' => $step?->id,
                'reported_by_id' => $qa?->id,
            ]
        );

        $bug2 = Bugs::updateOrCreate(
            ['title' => 'El orden de las tareas se pierde al recargar'],
            [
                'description' => 'Al mover tareas y recargar la página, el orden vuelve al original.',
                'severity' => 'medium',
                'status' => 'in_progress',
                'reported_by_id' => $qa?->id,
            ]
        );

        $bug3 = Bugs::updateOrCreate(
            ['title' => 'Fuga de memoria al dejar el tablero abierto'],
            [
                'description' => 'El uso de memoria crece de forma continua tras varios minutos.',
                'severity' => 'low',
                'status' => 'resolved',
                'reported_by_id' => $qa?->id,
            ]
        );

        $task = Task::where('title', 'Implementar login de usuarios')->first();
        $task2 = Task::where('title', 'API de tareas')->first();

        if ($task) {
            $task->bugs()->syncWithoutDetaching([
                $bug1->id => ['relation_type' => 'fixes'],
                $bug3->id => ['relation_type' => 'related'],
            ]);
        }
        if ($task2) {
            $task2->bugs()->syncWithoutDetaching([$bug2->id => ['relation_type' => 'blocked_by']]);
        }

        Comments::firstOrCreate(
            [
                'commentable_type' => Bugs::class,
                'commentable_id' => $bug1->id,
                'user_id' => $dev?->id,
                'content' => 'Revisando, parece un problema en el manejo de errores del controlador.',
            ]
        );

        Comments::firstOrCreate(
            [
                'commentable_type' => Bugs::class,
                'commentable_id' => $bug2->id,
                'user_id' => $qa?->id,
                'content' => 'Adjunto una grabación reproduciendo el fallo.',
            ]
        );

        Recordings::firstOrCreate(
            ['title' => 'Reproducción bug de orden de tareas'],
            [
                'status' => 'completed',
                'file_path' => '/storage/recordings/bug2.webm',
                'mime_type' => 'video/webm',
                'duration_ms' => 42000,
                'file_size_bytes' => 2048000,
                'recordable_type' => Bugs::class,
                'recordable_id' => $bug2->id,
                'recorded_by_id' => $qa?->id,
                'finished_at' => now(),
            ]
        );
    }
}
