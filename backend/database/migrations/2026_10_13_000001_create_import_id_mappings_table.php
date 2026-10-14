<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_id_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source_key', 64);
            $table->string('source_table', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['source_key', 'source_table', 'source_id']);
            $table->index(['target_table', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_id_mappings');
    }
};
