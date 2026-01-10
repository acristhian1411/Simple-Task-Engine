<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("task_dependencies", function (Blueprint $table) {
            $table->id();
            $table->foreignId("task_id")->constrained("tasks")->cascadeOnDelete();
            $table->foreignId("depends_on_task_id")->constrained("tasks")->cascadeOnDelete();
            
            $table->softDeletes();
            $table->timestamps();

            $table->unique(["task_id", "depends_on_task_id"], "task_dep_unique");
            $table->index(["depends_on_task_id"], "task_dep_depends_idx");
        });

        DB::statement("ALTER TABLE task_dependencies ADD CONSTRAINT task_dep_self_check CHECK (task_id <> depends_on_task_id)");
    }

    public function down(): void
    {
        Schema::dropIfExists("task_dependencies");
    }
};
