<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description');
            $table->string('severity', 20);
            $table->string('status', 20)->default('open');
            $table->foreignId('test_case_id')->nullable()
                ->constrained('test_cases')
                ->nullOnDelete();
            $table->foreignId('test_step_id')->nullable()
                ->constrained('test_steps')
                ->nullOnDelete();
            $table->foreignId('reported_by_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
