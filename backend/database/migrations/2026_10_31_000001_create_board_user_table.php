<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['board_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_user');
    }
};
