<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->foreignId('component_id')->nullable()
                ->constrained('components')
                ->nullOnDelete();
        });

        // Los bugs existentes heredan el componente de su test case
        DB::statement('UPDATE bugs SET component_id = (
            SELECT test_cases.component_id FROM test_cases WHERE test_cases.id = bugs.test_case_id
        ) WHERE test_case_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('component_id');
        });
    }
};
