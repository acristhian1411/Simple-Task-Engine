<?php

namespace Database\Seeders;

use App\Models\Components;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComponentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $frontend = Components::updateOrCreate(
            ['name' => 'Frontend'],
            ['type' => 'frontend', 'description' => 'Aplicación web (React + Vite)', 'parent_id' => null]
        );

        $backend = Components::updateOrCreate(
            ['name' => 'Backend'],
            ['type' => 'backend', 'description' => 'API Laravel', 'parent_id' => null]
        );

        $auth = Components::updateOrCreate(
            ['name' => 'Servicio de autenticación'],
            ['type' => 'service', 'description' => 'Login y registro de usuarios', 'parent_id' => $backend->id]
        );

        $api = Components::updateOrCreate(
            ['name' => 'API REST'],
            ['type' => 'module', 'description' => 'Endpoints públicos y privados', 'parent_id' => $backend->id]
        );

        $bd = Components::updateOrCreate(
            ['name' => 'Base de datos'],
            ['type' => 'backend', 'description' => 'PostgreSQL', 'parent_id' => null]
        );

        $ui = Components::updateOrCreate(
            ['name' => 'UI de tableros'],
            ['type' => 'frontend', 'description' => 'Vistas de boards, listas y tareas', 'parent_id' => $frontend->id]
        );

        $ext = Components::updateOrCreate(
            ['name' => 'Extensión de navegador'],
            ['type' => 'service', 'description' => 'Grabadora de sesiones para reportar bugs', 'parent_id' => null]
        );

        Components::updateOrCreate(
            ['name' => 'Notificaciones'],
            ['type' => 'service', 'description' => 'Avisos por email y push', 'parent_id' => null]
        );

        $frontend->dependencies()->syncWithoutDetaching([$backend->id => ['criticality' => 'critical']]);
        $auth->dependencies()->syncWithoutDetaching([$bd->id => ['criticality' => 'critical']]);
        $api->dependencies()->syncWithoutDetaching([
            $bd->id => ['criticality' => 'critical'],
            $auth->id => ['criticality' => 'optional'],
        ]);
        $ui->dependencies()->syncWithoutDetaching([$api->id => ['criticality' => 'critical']]);
        $ext->dependencies()->syncWithoutDetaching([$api->id => ['criticality' => 'critical']]);
    }
}
