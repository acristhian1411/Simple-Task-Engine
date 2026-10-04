<?php

namespace Database\Seeders;

use App\Models\Components;
use App\Models\TestCaseActors;
use App\Models\TestCases;
use App\Models\TestSteps;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestCaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $auth = Components::where('name', 'Servicio de autenticación')->first();
        $ui = Components::where('name', 'UI de tableros')->first();

        $cases = [
            [
                'title' => 'Login con credenciales válidas',
                'component_id' => $auth?->id,
                'status' => 'passed',
                'preconditions' => 'Existe un usuario registrado.',
                'postconditions' => 'El usuario queda autenticado.',
                'expected_result' => 'Se redirige al dashboard.',
                'actors' => ['Usuario registrado'],
                'steps' => [
                    ['step_number' => 1, 'action' => 'Abrir la pantalla de login', 'expected' => 'Se muestra el formulario', 'type' => 'normal'],
                    ['step_number' => 2, 'action' => 'Ingresar email y contraseña válidos', 'expected' => 'Los campos se validan', 'type' => 'normal'],
                    ['step_number' => 3, 'action' => 'Pulsar "Entrar"', 'expected' => 'Redirige al dashboard', 'type' => 'normal'],
                ],
            ],
            [
                'title' => 'Login con contraseña incorrecta',
                'component_id' => $auth?->id,
                'status' => 'failed',
                'preconditions' => 'Existe un usuario registrado.',
                'expected_result' => 'Se muestra un mensaje de error.',
                'actors' => ['Usuario registrado'],
                'steps' => [
                    ['step_number' => 1, 'action' => 'Ingresar email válido y contraseña incorrecta', 'expected' => 'Aparece error de credenciales', 'type' => 'excepcion'],
                ],
            ],
            [
                'title' => 'Mover tarea entre columnas',
                'component_id' => $ui?->id,
                'status' => 'untested',
                'preconditions' => 'Existe un tablero con al menos una tarea.',
                'expected_result' => 'La tarea cambia de columna.',
                'actors' => ['Usuario autenticado'],
                'steps' => [
                    ['step_number' => 1, 'action' => 'Arrastrar una tarea a otra columna', 'expected' => 'La tarea se mueve', 'type' => 'normal'],
                    ['step_number' => 2, 'action' => 'Soltar la tarea', 'expected' => 'El orden se actualiza', 'type' => 'normal'],
                ],
            ],
        ];

        foreach ($cases as $case) {
            $testCase = TestCases::updateOrCreate(
                ['title' => $case['title']],
                [
                    'component_id' => $case['component_id'],
                    'status' => $case['status'],
                    'preconditions' => $case['preconditions'] ?? null,
                    'postconditions' => $case['postconditions'] ?? null,
                    'expected_result' => $case['expected_result'] ?? null,
                ]
            );

            foreach ($case['actors'] as $actor) {
                TestCaseActors::firstOrCreate(
                    ['test_case_id' => $testCase->id, 'actor_name' => $actor]
                );
            }

            foreach ($case['steps'] as $step) {
                TestSteps::firstOrCreate(
                    ['test_case_id' => $testCase->id, 'step_number' => $step['step_number']],
                    [
                        'action' => $step['action'],
                        'expected' => $step['expected'],
                        'type' => $step['type'],
                    ]
                );
            }
        }
    }
}
