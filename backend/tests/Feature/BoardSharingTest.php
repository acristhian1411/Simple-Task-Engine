<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BoardSharingTest extends TestCase
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

    private function createBoard(int $ownerId): array
    {
        $boardId = DB::table('boards')->insertGetId([
            'title' => 'Tablero de prueba',
            'user_id' => $ownerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listId = DB::table('lists')->insertGetId([
            'board_id' => $boardId,
            'title' => 'Por Hacer',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taskId = DB::table('tasks')->insertGetId([
            'list_id' => $listId,
            'title' => 'Tarjeta privada',
            'status' => 'todo',
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$boardId, $listId, $taskId];
    }

    private function user(int $id): User
    {
        return User::findOrFail($id);
    }

    public function test_board_appears_only_for_owner_and_members(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $memberId = $this->createUser('member@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [$boardId] = $this->createBoard($ownerId);

        DB::table('board_user')->insert([
            'board_id' => $boardId,
            'user_id' => $memberId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user($memberId));
        $this->getJson('/api/boards')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $boardId);

        Sanctum::actingAs($this->user($strangerId));
        $this->getJson('/api/boards')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_member_can_access_board_lists_and_tasks(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $memberId = $this->createUser('member@example.com');
        [$boardId, $listId, $taskId] = $this->createBoard($ownerId);

        DB::table('board_user')->insert([
            'board_id' => $boardId,
            'user_id' => $memberId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user($memberId));

        $this->getJson("/api/boards/{$boardId}")->assertOk();
        $this->getJson("/api/lists/{$listId}")->assertOk();
        $this->getJson("/api/tasks/{$taskId}")->assertOk();
    }

    public function test_stranger_cannot_access_board_lists_or_tasks(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [$boardId, $listId, $taskId] = $this->createBoard($ownerId);

        Sanctum::actingAs($this->user($strangerId));

        $this->getJson("/api/boards/{$boardId}")->assertNotFound();
        $this->getJson("/api/lists/{$listId}")->assertNotFound();
        $this->getJson("/api/tasks/{$taskId}")->assertNotFound();
        $this->getJson("/api/lists-with-tasks?board_id={$boardId}")
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_only_the_owner_can_manage_members(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $memberId = $this->createUser('member@example.com');
        $strangerId = $this->createUser('stranger@example.com');
        [$boardId] = $this->createBoard($ownerId);

        DB::table('board_user')->insert([
            'board_id' => $boardId,
            'user_id' => $memberId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->user($memberId));
        $this->postJson("/api/boards/{$boardId}/members", [
            'email' => 'stranger@example.com',
        ])->assertNotFound();
    }

    public function test_owner_can_add_and_remove_members_by_email(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        $invitedId = $this->createUser('invited@example.com');
        [$boardId] = $this->createBoard($ownerId);

        Sanctum::actingAs($this->user($ownerId));

        $this->postJson("/api/boards/{$boardId}/members", [
            'email' => 'invited@example.com',
        ])->assertCreated()->assertJsonFragment([
            'id' => $invitedId,
            'role' => 'member',
        ]);

        Sanctum::actingAs($this->user($invitedId));
        $this->getJson("/api/boards/{$boardId}")->assertOk();

        Sanctum::actingAs($this->user($ownerId));
        $this->deleteJson("/api/boards/{$boardId}/members/{$invitedId}")->assertOk();

        Sanctum::actingAs($this->user($invitedId));
        $this->getJson("/api/boards/{$boardId}")->assertNotFound();
    }

    public function test_unknown_email_is_rejected(): void
    {
        $ownerId = $this->createUser('owner@example.com');
        [$boardId] = $this->createBoard($ownerId);

        Sanctum::actingAs($this->user($ownerId));

        $this->postJson("/api/boards/{$boardId}/members", [
            'email' => 'nobody@example.com',
        ])->assertStatus(422);
    }
}
