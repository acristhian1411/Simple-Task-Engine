<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Tests\TestCase;

class ImportUseCasesCommandTest extends TestCase
{
    private ?string $sourcePath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTargetSchema();
    }

    protected function tearDown(): void
    {
        DB::purge('use_cases_import');
        if ($this->sourcePath !== null && is_file($this->sourcePath)) {
            unlink($this->sourcePath);
        }

        parent::tearDown();
    }

    public function test_it_imports_all_tables_and_is_idempotent(): void
    {
        $this->sourcePath = $this->createSourceDatabase();

        $this->artisan('use-cases:import', [
            'sqlite-file' => $this->sourcePath,
            '--source-key' => 'fixture',
        ])->expectsOutputToContain('Audits omitidas por referencia inexistente en SQLite: 6')->assertExitCode(0);

        $componentId = DB::table('import_id_mappings')
            ->where('source_key', 'fixture')->where('source_table', 'modules')->value('target_id');
        $caseId = DB::table('import_id_mappings')
            ->where('source_key', 'fixture')->where('source_table', 'test_cases')->value('target_id');
        $bugId = DB::table('import_id_mappings')
            ->where('source_key', 'fixture')->where('source_table', 'bugs')->value('target_id');
        $user = DB::table('users')->where('email', 'qa@example.test')->first();

        $this->assertNotSame('salt:' . str_repeat('a', 128), $user->password);
        $this->assertSame((int) $componentId, (int) DB::table('test_cases')->value('component_id'));
        $this->assertSame('App\\Models\\Components', DB::table('comments')->value('commentable_type'));
        $this->assertSame((int) $componentId, (int) DB::table('comments')->value('commentable_id'));
        $this->assertSame('App\\Models\\TestCases', DB::table('audits')->value('auditable_type'));
        $this->assertSame((int) $caseId, (int) DB::table('audits')->value('auditable_id'));
        $this->assertSame(['legacy_details' => '{"changed":true}'], json_decode(DB::table('audits')->value('new_values'), true));
        $this->assertNotNull(DB::table('extension_tokens')->value('revoked_at'));
        $this->assertSame((int) $bugId, (int) DB::table('recordings')->value('recordable_id'));
        $this->assertSame('App\\Models\\Bugs', DB::table('recordings')->value('recordable_type'));
        $this->assertSame('2023-11-14 22:20:00', DB::table('recordings')->value('canceled_at'));
        $this->assertSame('2023-11-14 22:13:20', DB::table('bugs')->value('created_at'));
        $this->assertDatabaseCount('import_id_mappings', 10);
        $this->assertDatabaseCount('audits', 1);

        $this->artisan('use-cases:import', [
            'sqlite-file' => $this->sourcePath,
            '--source-key' => 'fixture',
        ])->expectsOutputToContain('Audits omitidas por referencia inexistente en SQLite: 6')->assertExitCode(0);

        foreach (['users', 'components', 'test_cases', 'test_steps', 'test_case_actors', 'bugs', 'comments', 'audits', 'extension_tokens', 'recordings'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertDatabaseCount('import_id_mappings', 10);
    }

    public function test_dry_run_validates_without_persisting_rows(): void
    {
        $this->sourcePath = $this->createSourceDatabase();

        $this->artisan('use-cases:import', [
            'sqlite-file' => $this->sourcePath,
            '--source-key' => 'dry-run',
            '--dry-run' => true,
        ])->assertExitCode(0);

        foreach (['users', 'components', 'test_cases', 'test_steps', 'test_case_actors', 'bugs', 'comments', 'audits', 'extension_tokens', 'recordings', 'import_id_mappings'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_existing_email_is_linked_without_overwriting_the_user(): void
    {
        $this->sourcePath = $this->createSourceDatabase();
        DB::table('users')->insert([
            'name' => 'Existing Account',
            'email' => 'qa@example.test',
            'password' => 'keep-this-password-hash',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $existingUserId = DB::table('users')->value('id');

        $this->artisan('use-cases:import', [
            'sqlite-file' => $this->sourcePath,
            '--source-key' => 'existing-user',
        ])->assertExitCode(0);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Existing Account', DB::table('users')->value('name'));
        $this->assertSame('keep-this-password-hash', DB::table('users')->value('password'));
        $this->assertSame($existingUserId, DB::table('import_id_mappings')->where('source_table', 'users')->value('target_id'));
    }

    public function test_invalid_step_type_rolls_back_the_entire_import(): void
    {
        $this->sourcePath = $this->createSourceDatabase('unexpected');

        $this->artisan('use-cases:import', [
            'sqlite-file' => $this->sourcePath,
            '--source-key' => 'invalid-step',
        ])->assertExitCode(1);

        foreach (['users', 'components', 'test_cases', 'test_steps', 'import_id_mappings'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    private function createTargetSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('components')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('component_id')->nullable()->constrained('components')->nullOnDelete();
            $table->text('preconditions')->nullable();
            $table->text('postconditions')->nullable();
            $table->text('expected_result')->nullable();
            $table->string('status')->default('untested');
            $table->timestamps();
        });

        Schema::create('test_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
            $table->unsignedInteger('step_number');
            $table->text('action')->nullable();
            $table->text('expected')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('test_case_actors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
            $table->string('actor_name');
            $table->timestamps();
        });

        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('severity');
            $table->string('status');
            $table->foreignId('test_case_id')->nullable()->constrained('test_cases')->nullOnDelete();
            $table->foreignId('test_step_id')->nullable()->constrained('test_steps')->nullOnDelete();
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('extension_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('label')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('commentable');
            $table->timestamps();
        });

        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->morphs('auditable');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('status');
            $table->string('file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('console_log_path')->nullable();
            $table->string('network_log_path')->nullable();
            $table->nullableMorphs('recordable');
            $table->foreignId('recorded_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_id_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 64);
            $table->string('source_table', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
            $table->unique(['source_key', 'source_table', 'source_id']);
        });
    }

    private function createSourceDatabase(string $stepType = 'normal'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'use-cases-');
        $this->assertNotFalse($path);
        $this->sourcePath = $path;
        $database = new PDO('sqlite:' . $path);

        foreach ([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL, password_hash TEXT NOT NULL, created_at INTEGER NOT NULL)',
            'CREATE TABLE modules (id INTEGER PRIMARY KEY, name TEXT NOT NULL, description TEXT)',
            'CREATE TABLE test_cases (id INTEGER PRIMARY KEY, title TEXT NOT NULL, description TEXT, module_id INTEGER, preconditions TEXT, postconditions TEXT, expected_result TEXT, status TEXT)',
            'CREATE TABLE test_steps (id INTEGER PRIMARY KEY, test_case_id INTEGER, step_number INTEGER NOT NULL, action TEXT, expected TEXT, type TEXT)',
            'CREATE TABLE test_case_actors (id INTEGER PRIMARY KEY, test_case_id INTEGER, actor_name TEXT NOT NULL)',
            'CREATE TABLE bugs (id INTEGER PRIMARY KEY, title TEXT NOT NULL, description TEXT NOT NULL, severity TEXT NOT NULL, status TEXT NOT NULL, test_case_id INTEGER, test_step_id INTEGER, reported_by_id INTEGER, created_at INTEGER NOT NULL)',
            'CREATE TABLE comments (id INTEGER PRIMARY KEY, content TEXT NOT NULL, user_id INTEGER, ref_table TEXT NOT NULL, ref_id INTEGER NOT NULL, created_at INTEGER NOT NULL)',
            'CREATE TABLE audits (id INTEGER PRIMARY KEY, action TEXT NOT NULL, user_id INTEGER, ref_table TEXT NOT NULL, ref_id INTEGER NOT NULL, details TEXT, created_at INTEGER NOT NULL)',
            'CREATE TABLE extension_tokens (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, token_hash TEXT NOT NULL, label TEXT, created_at INTEGER NOT NULL, last_used_at INTEGER, revoked_at INTEGER)',
            'CREATE TABLE recordings (id INTEGER PRIMARY KEY, recorded_by_id INTEGER NOT NULL, file_path TEXT, title TEXT, mime_type TEXT, ref_table TEXT, ref_id INTEGER, duration_ms INTEGER, file_size_bytes INTEGER, network_log_path TEXT, console_log_path TEXT, status TEXT NOT NULL, created_at INTEGER NOT NULL, finished_at INTEGER, canceled_at INTEGER)',
        ] as $statement) {
            $database->exec($statement);
        }

        $database->exec("INSERT INTO users VALUES (7, 'QA User', 'qa@example.test', 'salt:" . str_repeat('a', 128) . "', 1700000000)");
        $database->exec("INSERT INTO modules VALUES (3, 'Payments', 'Payment flows')");
        $database->exec("INSERT INTO test_cases VALUES (11, 'Checkout', 'Complete checkout', 3, 'Cart has items', 'Order exists', 'Success', 'untested')");
        $stepInsert = $database->prepare('INSERT INTO test_steps VALUES (12, 11, 1, ?, ?, ?)');
        $stepInsert->execute(['Submit checkout', 'Order is created', $stepType]);
        $database->exec("INSERT INTO test_case_actors VALUES (13, 11, 'Customer')");
        $database->exec("INSERT INTO bugs VALUES (15, 'Checkout error', 'Checkout fails', 'high', 'open', 11, 12, 7, 1700000000)");
        $database->exec("INSERT INTO comments VALUES (4, 'Module note', 7, 'modules', 3, 1700000000)");
        $database->exec("INSERT INTO audits VALUES (5, 'created', 7, 'test_cases', 11, '{\"changed\":true}', 1700000000)");
        $database->exec("INSERT INTO audits VALUES (6, 'deleted-case-update', 7, 'test_cases', 999, NULL, 1700000100)");
        $database->exec("INSERT INTO extension_tokens VALUES (2, 7, '" . str_repeat('b', 64) . "', 'QA browser', 1700000000, 1700000100, NULL)");
        $database->exec("INSERT INTO recordings VALUES (8, 7, 'media/demo.webm', 'Checkout run', 'video/webm', 'bugs', 15, 6500, 1024, 'logs/network.jsonl', 'logs/console.jsonl', 'completed', 1700000000, 1700000300, 1700000400)");
        $database = null;

        return $path;
    }
}
