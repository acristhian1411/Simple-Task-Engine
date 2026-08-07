<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_component', function (Blueprint $table) {
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained('components')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['task_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_component');
    }
};
