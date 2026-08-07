<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('component_id')->nullable()->after('list_id')
                ->constrained('components')
                ->nullOnDelete();

            $table->foreignId('assigned_to')->nullable()->after('component_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('component_id');
            $table->dropConstrainedForeignId('assigned_to');
        });
    }
};
