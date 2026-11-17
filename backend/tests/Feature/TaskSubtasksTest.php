<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskSubtasksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('board_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id');
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id');
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id');
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_task_subtasks_are_not_cut_at_the_first_page(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);
        $boardId = DB::table('boards')->insertGetId(['title' => 'Tablero', 'user_id' => $userId]);
        $listId = DB::table('lists')->insertGetId(['board_id' => $boardId, 'title' => 'Por Hacer']);
        $taskId = DB::table('tasks')->insertGetId(['list_id' => $listId, 'title' => 'Tarea']);

        foreach (range(1, 15) as $i) {
            DB::table('subtasks')->insert([
                'task_id' => $taskId,
                'title' => "Subtarea {$i}",
                'is_completed' => $i === 1,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        Sanctum::actingAs(User::findOrFail($userId));

        $response = $this->getJson("/api/tasks/{$taskId}/subtasks")->assertOk();

        $this->assertCount(15, $response->json('data'));
        $this->assertSame('Subtarea 1', $response->json('data.0.title'));
        $this->assertSame('Subtarea 15', $response->json('data.14.title'));
    }
}
