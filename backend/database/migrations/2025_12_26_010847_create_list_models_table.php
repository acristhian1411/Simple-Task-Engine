<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("lists", function (Blueprint $table) {
            $table->id();
            $table->foreignId("board_id")->constrained("boards")->cascadeOnDelete();
            $table->string("title");
            $table->unsignedInteger("order")->default(0);
            
            $table->softDeletes();
            $table->timestamps();
            $table->index(["board_id", "order"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("lists");
    }
};
