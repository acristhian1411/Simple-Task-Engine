<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Bugs;
use App\Models\Comments;
use App\Models\Components;
use App\Models\ExtentionTokens;
use App\Models\Recordings;
use App\Models\TestCaseActors;
use App\Models\TestCases;
use App\Models\TestSteps;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UseCasesImporter
{
    private const SOURCE_REQUIRED_COLUMNS = [
        'users' => ['id', 'name', 'email', 'password_hash', 'created_at'],
        'modules' => ['id', 'name', 'description'],
        'test_cases' => ['id', 'title', 'description', 'module_id', 'preconditions', 'postconditions', 'expected_result'],
        'test_steps' => ['id', 'test_case_id', 'step_number', 'action', 'expected'],
        'test_case_actors' => ['id', 'test_case_id', 'actor_name'],
        'bugs' => ['id', 'title', 'description', 'severity', 'status', 'test_case_id', 'test_step_id', 'reported_by_id', 'created_at'],
        'comments' => ['id', 'content', 'user_id', 'ref_table', 'ref_id', 'created_at'],
        'audits' => ['id', 'action', 'user_id', 'ref_table', 'ref_id', 'created_at'],
        'extension_tokens' => ['id', 'user_id', 'token_hash', 'label', 'created_at', 'last_used_at', 'revoked_at'],
        'recordings' => ['id', 'recorded_by_id', 'file_path', 'title', 'mime_type', 'ref_table', 'ref_id', 'duration_ms', 'file_size_bytes', 'network_log_path', 'console_log_path', 'status', 'created_at', 'finished_at'],
    ];

    private const MORPH_MODELS = [
        'users' => User::class,
        'modules' => Components::class,
        'test_cases' => TestCases::class,
        'test_steps' => TestSteps::class,
        'test_case_actors' => TestCaseActors::class,
        'bugs' => Bugs::class,
        'comments' => Comments::class,
        'audits' => Audit::class,
        'extension_tokens' => ExtentionTokens::class,
        'recordings' => Recordings::class,
    ];

    private Connection $source;

    private Connection $target;

    private string $sourceKey;

    /** @var array<string, array{rows: int, created: int, linked: int, already_imported: int, skipped: int}> */
    private array $tableCounts = [];

    /** @var array<string, list<int>> */
    private array $skippedIds = [];

    private int $fileReferencesNotCopied = 0;

    public function import(string $sqlitePath, string $sourceKey = 'use_cases', bool $dryRun = false): array
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('The pdo_sqlite PHP extension is required to import this database.');
        }

        $realPath = realpath($sqlitePath);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            throw new RuntimeException("SQLite source file does not exist or is not readable: {$sqlitePath}");
        }

        if ($sourceKey === '' || strlen($sourceKey) > 64) {
            throw new RuntimeException('The source key must contain between 1 and 64 characters.');
        }

        $this->sourceKey = $sourceKey;
        $this->tableCounts = [];
        $this->skippedIds = [];
        $this->fileReferencesNotCopied = 0;

        Config::set('database.connections.use_cases_import.database', $realPath);
        Config::set('database.connections.use_cases_import.options', [
            \PDO::SQLITE_ATTR_OPEN_FLAGS => \PDO::SQLITE_OPEN_READONLY,
        ]);
        DB::purge('use_cases_import');

        try {
            $this->source = DB::connection('use_cases_import');
            $this->target = DB::connection();
            $this->validateSource();
            $this->validateTarget();

            $this->target->beginTransaction();
            try {
                $this->runImport();

                if ($dryRun) {
                    $this->target->rollBack();
                } else {
                    $this->target->commit();
                }
            } catch (Throwable $exception) {
                if ($this->target->transactionLevel() > 0) {
                    $this->target->rollBack();
                }

                throw $exception;
            }

            return [
                'tables' => $this->tableCounts,
                'skipped_ids' => $this->skippedIds,
                'file_references_not_copied' => $this->fileReferencesNotCopied,
                'dry_run' => $dryRun,
            ];
        } finally {
            DB::disconnect('use_cases_import');
        }
    }

    private function validateSource(): void
    {
        try {
            $integrity = $this->source->select('PRAGMA quick_check');
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not open the SQLite source in read-only mode: ' . $exception->getMessage(), previous: $exception);
        }

        $integrityResult = isset($integrity[0]) ? array_values((array) $integrity[0])[0] : null;
        if ($integrityResult !== 'ok') {
            throw new RuntimeException('SQLite integrity check failed: ' . (string) $integrityResult);
        }

        $schema = $this->source->getSchemaBuilder();
        foreach (self::SOURCE_REQUIRED_COLUMNS as $table => $requiredColumns) {
            if (!$schema->hasTable($table)) {
                throw new RuntimeException("SQLite source is missing required table [{$table}].");
            }

            $columns = $schema->getColumnListing($table);
            $missing = array_diff($requiredColumns, $columns);
            if ($missing !== []) {
                throw new RuntimeException("SQLite source table [{$table}] is missing columns: " . implode(', ', $missing) . '.');
            }
        }
    }

    private function validateTarget(): void
    {
        $schema = $this->target->getSchemaBuilder();
        $requiredTables = [
            'users',
            'components',
            'test_cases',
            'test_steps',
            'test_case_actors',
            'bugs',
            'comments',
            'audits',
            'extension_tokens',
            'recordings',
            'import_id_mappings',
        ];

        foreach ($requiredTables as $table) {
            if (!$schema->hasTable($table)) {
                throw new RuntimeException("PostgreSQL target is missing required table [{$table}]; run migrations first.");
            }
        }

        if (!in_array('canceled_at', $schema->getColumnListing('recordings'), true)) {
            throw new RuntimeException('PostgreSQL target is missing recordings.canceled_at; run migrations first.');
        }
    }

    private function runImport(): void
    {
        $this->importTable('users', 'users', fn($row) => $this->insertUser($row), function ($row) {
            return $this->target->table('users')->where('email', $row->email)->value('id');
        });

        $this->importTable('modules', 'components', fn($row) => $this->target->table('components')->insertGetId([
            'name' => $row->name,
            'type' => 'module',
            'description' => $row->description,
            'parent_id' => null,
            'created_at' => null,
            'updated_at' => null,
        ]));

        $this->importTable('test_cases', 'test_cases', function ($row) {
            return $this->target->table('test_cases')->insertGetId([
                'title' => $row->title,
                'description' => $row->description,
                'component_id' => $row->module_id === null ? null : $this->mappedId('modules', (int) $row->module_id),
                'preconditions' => $row->preconditions,
                'postconditions' => $row->postconditions,
                'expected_result' => $row->expected_result,
                'status' => $this->value($row, 'status') ?? 'untested',
                'created_at' => null,
                'updated_at' => null,
            ]);
        });

        $this->importTable('test_steps', 'test_steps', function ($row) {
            $testCaseId = $this->requiredMappedId('test_cases', $row->test_case_id, 'test_steps', $row->id);
            $type = $this->value($row, 'type');
            if ($type !== null && !in_array($type, ['normal', 'alternativo', 'excepcion'], true)) {
                throw new RuntimeException("test_steps#{$row->id} has unsupported type [{$type}].");
            }

            return $this->target->table('test_steps')->insertGetId([
                'test_case_id' => $testCaseId,
                'step_number' => $row->step_number,
                'action' => $row->action,
                'expected' => $row->expected,
                'type' => $type,
                'created_at' => null,
                'updated_at' => null,
            ]);
        });

        $this->importTable('test_case_actors', 'test_case_actors', function ($row) {
            return $this->target->table('test_case_actors')->insertGetId([
                'test_case_id' => $this->requiredMappedId('test_cases', $row->test_case_id, 'test_case_actors', $row->id),
                'actor_name' => $row->actor_name,
                'created_at' => null,
                'updated_at' => null,
            ]);
        });

        $this->importTable('bugs', 'bugs', function ($row) {
            return $this->target->table('bugs')->insertGetId([
                'title' => $row->title,
                'description' => $row->description,
                'severity' => $row->severity,
                'status' => $row->status,
                'test_case_id' => $this->optionalMappedId('test_cases', $row->test_case_id, 'bugs', $row->id),
                'test_step_id' => $this->optionalMappedId('test_steps', $row->test_step_id, 'bugs', $row->id),
                'reported_by_id' => $this->optionalMappedId('users', $row->reported_by_id, 'bugs', $row->id),
                'created_at' => $this->timestamp($row->created_at, 'bugs', $row->id),
                'updated_at' => $this->timestamp($row->created_at, 'bugs', $row->id),
            ]);
        });

        $this->importTable('extension_tokens', 'extension_tokens', function ($row) {
            $createdAt = $this->timestamp($row->created_at, 'extension_tokens', $row->id);

            return $this->target->table('extension_tokens')->insertGetId([
                'user_id' => $this->requiredMappedId('users', $row->user_id, 'extension_tokens', $row->id),
                'token_hash' => $row->token_hash,
                'label' => $row->label,
                'last_used_at' => $this->timestamp($row->last_used_at, 'extension_tokens', $row->id),
                'revoked_at' => $this->timestamp($row->revoked_at, 'extension_tokens', $row->id) ?? now()->toDateTimeString(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });

        $this->importTable('comments', 'comments', function ($row) {
            [$type, $id] = $this->resolveMorph($row->ref_table, $row->ref_id, 'comments', $row->id);
            $createdAt = $this->timestamp($row->created_at, 'comments', $row->id);

            return $this->target->table('comments')->insertGetId([
                'content' => $row->content,
                'user_id' => $this->optionalMappedId('users', $row->user_id, 'comments', $row->id),
                'commentable_type' => $type,
                'commentable_id' => $id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });

        $this->importTable('audits', 'audits', function ($row) {
            if (!$this->sourceMorphTargetExists($row->ref_table, $row->ref_id, 'audits', $row->id)) {
                $this->skippedIds['audits'][] = (int) $row->id;

                return null;
            }

            [$type, $id] = $this->resolveMorph($row->ref_table, $row->ref_id, 'audits', $row->id);
            $createdAt = $this->timestamp($row->created_at, 'audits', $row->id);
            $details = $this->value($row, 'details');

            return $this->target->table('audits')->insertGetId([
                'user_type' => $row->user_id === null ? null : User::class,
                'user_id' => $this->optionalMappedId('users', $row->user_id, 'audits', $row->id),
                'event' => $row->action,
                'auditable_type' => $type,
                'auditable_id' => $id,
                'old_values' => null,
                'new_values' => json_encode(['legacy_details' => $details], JSON_THROW_ON_ERROR),
                'url' => null,
                'ip_address' => null,
                'user_agent' => null,
                'tags' => 'use_cases-import',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });

        $this->importTable('recordings', 'recordings', function ($row) {
            [$type, $id] = $this->resolveOptionalMorph($row->ref_table, $row->ref_id, 'recordings', $row->id);
            $createdAt = $this->timestamp($row->created_at, 'recordings', $row->id);
            foreach (['file_path', 'console_log_path', 'network_log_path'] as $pathColumn) {
                if ($this->value($row, $pathColumn) !== null) {
                    $this->fileReferencesNotCopied++;
                }
            }

            return $this->target->table('recordings')->insertGetId([
                'title' => $row->title,
                'status' => $row->status,
                'file_path' => $row->file_path,
                'mime_type' => $row->mime_type,
                'duration_ms' => $row->duration_ms,
                'file_size_bytes' => $row->file_size_bytes,
                'console_log_path' => $row->console_log_path,
                'network_log_path' => $row->network_log_path,
                'recordable_type' => $type,
                'recordable_id' => $id,
                'recorded_by_id' => $this->requiredMappedId('users', $row->recorded_by_id, 'recordings', $row->id),
                'finished_at' => $this->timestamp($row->finished_at, 'recordings', $row->id),
                'canceled_at' => $this->timestamp($this->value($row, 'canceled_at'), 'recordings', $row->id),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });
    }

    private function insertUser(object $row): int
    {
        $createdAt = $this->timestamp($row->created_at, 'users', $row->id);

        return $this->target->table('users')->insertGetId([
            'name' => $row->name,
            'email' => $row->email,
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function importTable(string $sourceTable, string $targetTable, callable $insert, ?callable $reuse = null): void
    {
        $counts = ['rows' => 0, 'created' => 0, 'linked' => 0, 'already_imported' => 0, 'skipped' => 0];

        foreach ($this->source->table($sourceTable)->orderBy('id')->cursor() as $row) {
            $counts['rows']++;
            $sourceId = (int) $row->id;
            $existingMapping = $this->target->table('import_id_mappings')
                ->where('source_key', $this->sourceKey)
                ->where('source_table', $sourceTable)
                ->where('source_id', $sourceId)
                ->first();

            if ($existingMapping !== null) {
                if (
                    $existingMapping->target_table !== $targetTable
                    || !$this->target->table($targetTable)->where('id', $existingMapping->target_id)->exists()
                ) {
                    throw new RuntimeException("Stale or incompatible ID mapping for {$sourceTable}#{$sourceId}.");
                }

                $counts['already_imported']++;

                continue;
            }

            $targetId = $reuse === null ? null : $reuse($row);
            if ($targetId === null) {
                $targetId = $insert($row);
                if ($targetId === null) {
                    $counts['skipped']++;

                    continue;
                }

                $counts['created']++;
            } else {
                $counts['linked']++;
            }

            $this->target->table('import_id_mappings')->insert([
                'source_key' => $this->sourceKey,
                'source_table' => $sourceTable,
                'source_id' => $sourceId,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->tableCounts[$sourceTable] = $counts;
    }

    private function mappedId(string $sourceTable, int $sourceId): ?int
    {
        $targetId = $this->target->table('import_id_mappings')
            ->where('source_key', $this->sourceKey)
            ->where('source_table', $sourceTable)
            ->where('source_id', $sourceId)
            ->value('target_id');

        return $targetId === null ? null : (int) $targetId;
    }

    private function requiredMappedId(string $sourceTable, mixed $sourceId, string $table, int|string $rowId): int
    {
        if ($sourceId === null) {
            throw new RuntimeException("{$table}#{$rowId} is missing required {$sourceTable} reference.");
        }

        $targetId = $this->mappedId($sourceTable, (int) $sourceId);
        if ($targetId === null) {
            throw new RuntimeException("{$table}#{$rowId} references missing {$sourceTable}#{$sourceId}.");
        }

        return $targetId;
    }

    private function optionalMappedId(string $sourceTable, mixed $sourceId, string $table, int|string $rowId): ?int
    {
        return $sourceId === null ? null : $this->requiredMappedId($sourceTable, $sourceId, $table, $rowId);
    }

    /** @return array{0: string, 1: int} */
    private function resolveMorph(mixed $sourceTable, mixed $sourceId, string $table, int|string $rowId): array
    {
        if ($sourceTable === null || $sourceId === null) {
            throw new RuntimeException("{$table}#{$rowId} is missing its polymorphic reference.");
        }

        $sourceTable = (string) $sourceTable;
        if (!isset(self::MORPH_MODELS[$sourceTable])) {
            throw new RuntimeException("{$table}#{$rowId} references unsupported polymorphic table [{$sourceTable}].");
        }

        return [
            self::MORPH_MODELS[$sourceTable],
            $this->requiredMappedId($sourceTable, $sourceId, $table, $rowId),
        ];
    }

    private function sourceMorphTargetExists(mixed $sourceTable, mixed $sourceId, string $table, int|string $rowId): bool
    {
        if ($sourceTable === null || $sourceId === null) {
            throw new RuntimeException("{$table}#{$rowId} is missing its polymorphic reference.");
        }

        $sourceTable = (string) $sourceTable;
        if (!isset(self::MORPH_MODELS[$sourceTable])) {
            throw new RuntimeException("{$table}#{$rowId} references unsupported polymorphic table [{$sourceTable}].");
        }

        return $this->source->table($sourceTable)->where('id', $sourceId)->exists();
    }

    /** @return array{0: string|null, 1: int|null} */
    private function resolveOptionalMorph(mixed $sourceTable, mixed $sourceId, string $table, int|string $rowId): array
    {
        if ($sourceTable === null && $sourceId === null) {
            return [null, null];
        }

        return $this->resolveMorph($sourceTable, $sourceId, $table, $rowId);
    }

    private function timestamp(mixed $value, string $table, int|string $rowId): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $seconds = (float) $value;
                if (abs($seconds) >= 100_000_000_000) {
                    $seconds /= 1000;
                }

                return Carbon::createFromTimestamp((int) $seconds, 'UTC')->toDateTimeString();
            }

            return Carbon::parse($value)->utc()->toDateTimeString();
        } catch (Throwable $exception) {
            throw new RuntimeException("{$table}#{$rowId} has invalid timestamp [{$value}].", previous: $exception);
        }
    }

    private function value(object $row, string $column): mixed
    {
        return property_exists($row, $column) ? $row->{$column} : null;
    }
}
