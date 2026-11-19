<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    private function createUser(string $email, bool $isAdmin = false): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'User '.$email,
            'email' => $email,
            'password' => Hash::make('initial-password'),
            'is_admin' => $isAdmin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    public function test_only_admins_can_manage_users(): void
    {
        $regular = $this->createUser('member@example.com');
        Sanctum::actingAs($regular);

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [])->assertForbidden();
    }

    public function test_admin_can_create_edit_reset_and_deactivate_users(): void
    {
        $admin = $this->createUser('admin@example.com', true);
        Sanctum::actingAs($admin);

        $this->postJson('/api/users', [
            'name' => 'New Member',
            'email' => 'new@example.com',
            'password' => 'temporary-password',
            'password_confirmation' => 'temporary-password',
            'is_admin' => true,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'New Member')
            ->assertJsonPath('data.is_admin', false)
            ->assertJsonMissingPath('data.password');

        $member = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertFalse($member->is_admin);
        $this->assertTrue(Hash::check('temporary-password', $member->password));

        $this->putJson("/api/users/{$member->id}", [
            'name' => 'Updated Member',
            'email' => 'updated@example.com',
            'is_admin' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Updated Member');
        $this->assertFalse($member->fresh()->is_admin);

        $member->createToken('test');
        $this->postJson("/api/users/{$member->id}/reset-password", [
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('replacement-password', $member->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->deleteJson("/api/users/{$member->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $member->id]);
    }

    public function test_admin_cannot_delete_self_or_the_last_admin(): void
    {
        $admin = $this->createUser('admin@example.com', true);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/users/{$admin->id}")->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);

        $otherAdmin = $this->createUser('other-admin@example.com', true);
        $otherAdmin->delete();
        $this->deleteJson("/api/users/{$admin->id}")->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_public_registration_endpoint_is_removed(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Uninvited',
            'email' => 'uninvited@example.com',
            'password' => 'password',
        ])->assertNotFound();
    }
}
