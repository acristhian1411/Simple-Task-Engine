<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignId('component_id')->nullable()
                ->constrained('components')
                ->nullOnDelete();
            $table->text('preconditions')->nullable();
            $table->text('postconditions')->nullable();
            $table->text('expected_result')->nullable();
            $table->string('status', 20)->default('untested');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
