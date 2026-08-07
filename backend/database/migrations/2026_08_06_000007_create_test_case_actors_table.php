<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_case_actors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')
                ->constrained('test_cases')
                ->cascadeOnDelete();
            $table->string('actor_name', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_case_actors');
    }
};
