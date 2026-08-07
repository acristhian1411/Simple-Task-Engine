<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150)->nullable();
            $table->string('status', 20)->default('recording'); // recording | processing | completed | failed
            $table->string('file_path')->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('console_log_path')->nullable(); // JSONL
            $table->string('network_log_path')->nullable();

            // Polimórfico: recordable_type / recordable_id -> TestCase | Bug | Task
            $table->nullableMorphs('recordable');

            $table->foreignId('recorded_by_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
