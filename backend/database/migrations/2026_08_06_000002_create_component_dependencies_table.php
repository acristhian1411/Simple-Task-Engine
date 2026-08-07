<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')
                ->constrained('components')
                ->cascadeOnDelete();
            $table->foreignId('depends_on_id')
                ->constrained('components')
                ->cascadeOnDelete();
            $table->enum('criticality', ['critical', 'optional'])->default('optional');
            $table->timestamps();

            $table->unique(['component_id', 'depends_on_id']);
        });

        // Postgres: un componente no puede depender de sí mismo
        DB::statement(
            'ALTER TABLE component_dependencies
             ADD CONSTRAINT chk_component_dependencies_not_self
             CHECK (component_id <> depends_on_id)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('component_dependencies');
    }
};
