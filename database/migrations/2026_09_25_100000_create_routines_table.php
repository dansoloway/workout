<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('owner_scope')->default(0);
            $table->string('name');
            $table->string('kind')->default('workout');
            $table->unsignedTinyInteger('rounds')->default(1);
            $table->unsignedSmallInteger('rest_seconds')->default(0);
            $table->boolean('is_current')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['owner_scope', 'kind', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
