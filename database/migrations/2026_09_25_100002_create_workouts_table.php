<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('owner_scope')->default(0);
            $table->foreignId('routine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('routine_name');
            $table->string('kind')->default('workout');
            $table->date('workout_date');
            $table->unsignedTinyInteger('rounds');
            $table->unsignedSmallInteger('rest_seconds')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_scope', 'routine_id', 'workout_date']);
            $table->index(['owner_scope', 'kind', 'workout_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
