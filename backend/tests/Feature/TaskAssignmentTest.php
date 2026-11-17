<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id');
            $table->string('title');
            $table->unsignedInteger('order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('todo');
            $table->unsignedInteger('order')->default(0);
            $table->unsignedBigInteger('component_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('board_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['board_id', 'user_id']);
        });

        // Solo lo necesario para el eager load de lists-with-tasks
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('task_component', function (Blueprint $table) {
            $table->foreignId('task_id');
            $table->foreignId('component_id');
        });
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('task_bug', function (Blueprint $table) {
            $table->foreignId('task_id');
            $table->foreignId('bug_id');
            $table->string('relation_type')->default('related');
        });
    }

    private function createUser(string $email): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'User ' . $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Crea un tablero con una lista y una tarea; devuelve [boardId, listId, taskId].
     */
    private function createBoard(int $ownerId, array $memberIds = []): array
    {
        $boardId = DB::table('boards')->insertGetId([
            'title' => 'Tablero de prueba',
            'user_id' => $ownerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($memberIds as $memberId) {
            DB::table('board_user')->insert([
                'board_id' => $boardId,
                'user_id' => $memberId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $listId = DB::table('lists')->insertGetId([
            'board_id' => $boardId,
            'title' => 'Por Hacer',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taskId = $this->createTask($listId);

        return [$boardId, $listId, $taskId];
    }

    private function createTask(int $listId, ?int $assigneeId = null): int
    {
        return DB::table('tasks')->insertGetId([
            'list_id' => $listId,
            'title' => 'Tarjeta',
            'status' => 'todo',
            'order' => 0,
            'assigned_to' => $assigneeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function actingAsUser(int $id): void
    {
        Sanctum::actingAs(User::findOrFail($id));
    }

    private function assigneeOf(int $taskId): ?int
    {
        return DB::table('tasks')->where('id', $taskId)->value('assigned_to');
    }

    public function test_member_can_assign_another_member_and_gets_the_assignee_back(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $memberId = $this->createUser('member@example.com');
        [, , $taskId] = $this->createBoard($ownerId, [$memberId]);

        $this->actingAsUser($memberId);

        $this->putJson("/api/tasks/{$taskId}", ['assignee_id' => $ownerId])
            ->assertOk()
            ->assertJsonPath('data.assignee.id', $ownerId)
            ->assertJsonPath('data.assignee.email', 'owner@example.com')
            ->assertJsonMissingPath('data.assigned_to');

        $this->assertSame($ownerId, $this->assigneeOf($taskId));
    }

    public function test_assignee_can_be_cleared(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        [, $listId] = $this->createBoard($ownerId);
        $taskId = $this->createTask($listId, $ownerId);

        $this->actingAsUser($ownerId);

        $this->putJson("/api/tasks/{$taskId}", ['assignee_id' => null])
            ->assertOk()
            ->assertJsonPath('data.assignee', null);

        $this->assertNull($this->assigneeOf($taskId));
    }

    public function test_cannot_assign_a_user_who_is_not_a_board_member(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [, , $taskId] = $this->createBoard($ownerId);

        $this->actingAsUser($ownerId);

        $this->putJson("/api/tasks/{$taskId}", ['assignee_id' => $strangerId])
            ->assertStatus(422)
            ->assertJsonValidationErrors('assignee_id');

        $this->assertNull($this->assigneeOf($taskId));
    }

    public function test_task_can_be_created_with_an_assignee_member(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $memberId = $this->createUser('member@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [, $listId] = $this->createBoard($ownerId, [$memberId]);

        $this->actingAsUser($ownerId);

        $this->postJson('/api/tasks', [
            'list_id' => $listId,
            'title' => 'Nueva',
            'assignee_id' => $memberId,
        ])->assertCreated()->assertJsonPath('data.assignee.id', $memberId);

        $this->postJson('/api/tasks', [
            'list_id' => $listId,
            'title' => 'Otra',
            'assignee_id' => $strangerId,
        ])->assertStatus(422)->assertJsonValidationErrors('assignee_id');
    }

    public function test_assigned_to_cannot_be_set_directly(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [, , $taskId] = $this->createBoard($ownerId);

        $this->actingAsUser($ownerId);

        $this->putJson("/api/tasks/{$taskId}", ['assigned_to' => $strangerId])->assertOk();

        $this->assertNull($this->assigneeOf($taskId));
    }

    public function test_stranger_cannot_assign_tasks_of_a_board(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [, , $taskId] = $this->createBoard($ownerId);

        $this->actingAsUser($strangerId);

        $this->putJson("/api/tasks/{$taskId}", ['assignee_id' => $strangerId])->assertNotFound();

        $this->assertNull($this->assigneeOf($taskId));
    }

    public function test_board_tasks_include_the_assignee(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        [$boardId, $listId] = $this->createBoard($ownerId);
        $this->createTask($listId, $ownerId);

        $this->actingAsUser($ownerId);

        $response = $this->getJson("/api/lists-with-tasks?board_id={$boardId}")->assertOk();
        $tasks = collect($response->json('data.0.tasks'));

        $this->assertSame($ownerId, $tasks->firstWhere('assignee')['assignee']['id']);
    }

    public function test_removing_a_member_unassigns_only_their_tasks_on_that_board(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $memberId = $this->createUser('member@example.com');
        [$boardId, $listId] = $this->createBoard($ownerId, [$memberId]);
        [, $otherListId] = $this->createBoard($ownerId, [$memberId]);

        $memberTask = $this->createTask($listId, $memberId);
        $ownerTask = $this->createTask($listId, $ownerId);
        $otherBoardTask = $this->createTask($otherListId, $memberId);

        $this->actingAsUser($ownerId);
        $this->deleteJson("/api/boards/{$boardId}/members/{$memberId}")->assertOk();

        $this->assertNull($this->assigneeOf($memberTask));
        $this->assertSame($ownerId, $this->assigneeOf($ownerTask));
        $this->assertSame($memberId, $this->assigneeOf($otherBoardTask));
    }
}
