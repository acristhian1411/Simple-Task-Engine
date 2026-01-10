<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("tasks", function (Blueprint $table) {
            $table->id();
            $table->foreignId("list_id")->constrained("lists")->cascadeOnDelete();
            $table->string("title");
            $table->text("description")->nullable();
            $table->string("status")->default("todo");
            $table->unsignedInteger("order")->default(0);
            
            $table->softDeletes();
            $table->timestamps();
            $table->index(["list_id", "order"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tasks");
    }
};
