<?php

namespace App\Console\Commands;

use App\Services\UseCasesImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportUseCases extends Command
{
    protected $signature = 'use-cases:import
        {sqlite-file : Path to the use_cases SQLite database}
        {--source-key=use_cases : Stable identity for this source database}
        {--dry-run : Validate and roll back the import transaction}';

    protected $description = 'Import structured data from a use_cases SQLite database';

    public function handle(UseCasesImporter $importer): int
    {
        try {
            $summary = $importer->import(
                $this->argument('sqlite-file'),
                $this->option('source-key'),
                (bool) $this->option('dry-run'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        foreach ($summary['tables'] as $table => $counts) {
            $rows[] = [
                $table,
                $counts['rows'],
                $counts['created'],
                $counts['linked'],
                $counts['already_imported'],
                $counts['skipped'],
            ];
        }

        $this->table(['Tabla', 'Filas', 'Creadas', 'Enlazadas', 'Ya importadas', 'Omitidas'], $rows);
        foreach ($summary['skipped_ids'] as $table => $ids) {
            if ($ids !== []) {
                $this->warn(ucfirst($table) . ' omitidas por referencia inexistente en SQLite: ' . implode(', ', $ids));
            }
        }

        $this->info($summary['dry_run'] ? 'Simulación completada; no se guardaron filas.' : 'Importación completada.');
        $this->line('Referencias a archivos multimedia/logs sin copiar: ' . $summary['file_references_not_copied']);

        return self::SUCCESS;
    }
}
