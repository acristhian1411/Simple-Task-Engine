<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('type', 50)->nullable(); // ej: module, service, frontend, backend
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()
                ->constrained('components')
                ->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
