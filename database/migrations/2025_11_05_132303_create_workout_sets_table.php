<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('set_no')->default(1);
            $table->unsignedInteger('reps')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->unsignedTinyInteger('rir')->nullable();
            $table->unsignedInteger('rest_seconds')->nullable();
            $table->timestamps();
            $table->index(['workout_exercise_id','set_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
