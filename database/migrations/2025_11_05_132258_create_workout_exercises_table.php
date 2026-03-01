<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20)->default('manual');
            $table->string('external_id', 120)->nullable();
            $table->string('name', 120);
            $table->unsignedInteger('order_no')->default(1);
            $table->timestamps();
            $table->index(['workout_id', 'order_no']);
            $table->index(['source', 'external_id'], 'workout_exercises_source_external_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
