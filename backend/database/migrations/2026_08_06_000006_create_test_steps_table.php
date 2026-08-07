<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')
                ->constrained('test_cases')
                ->cascadeOnDelete();
            $table->unsignedInteger('step_number');
            $table->text('action')->nullable();
            $table->text('expected')->nullable();
            $table->enum('type', ['normal', 'alternativo', 'excepcion'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_steps');
    }
};
