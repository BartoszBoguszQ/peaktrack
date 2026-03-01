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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('source', 20)->default('manual');
            $table->string('external_id', 120)->nullable();
            $table->timestamps();
            $table->index(['name']);
            $table->index(['source', 'external_id']);
            $table->unique(['user_id', 'name'], 'exercises_user_name_unique');
            $table->unique(['source', 'external_id'], 'exercises_source_external_unique');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
