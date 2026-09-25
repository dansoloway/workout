<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('owner_scope')->default(0);
            $table->foreignId('routine_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('default_target');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['routine_id', 'active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
