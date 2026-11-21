<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExtensionSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('extension_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('token_hash', 64);
            $table->string('label')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function test_extension_token_detects_a_valid_or_revoked_session(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'QA User',
            'email' => 'qa@example.test',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $rawToken = 'extension-test-token';
        DB::table('extension_tokens')->insert([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $rawToken),
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', "Bearer {$rawToken}")
            ->getJson('/api/extension/session')
            ->assertOk()
            ->assertJsonPath('data.id', $userId)
            ->assertJsonPath('data.name', 'QA User');

        DB::table('extension_tokens')->update(['revoked_at' => now()]);
        $this->withHeader('Authorization', "Bearer {$rawToken}")
            ->getJson('/api/extension/session')
            ->assertUnauthorized();
    }
}
