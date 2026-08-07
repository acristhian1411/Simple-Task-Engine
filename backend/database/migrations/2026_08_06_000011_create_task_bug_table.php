<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_bug', function (Blueprint $table) {
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();
            $table->foreignId('bug_id')
                ->constrained('bugs')
                ->cascadeOnDelete();
            $table->enum('relation_type', ['fixes', 'blocked_by', 'related'])
                ->default('related');
            $table->timestamps();

            $table->primary(['task_id', 'bug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_bug');
    }
};
