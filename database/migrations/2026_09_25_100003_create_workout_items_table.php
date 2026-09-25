<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('target');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'round_number', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_items');
    }
};
