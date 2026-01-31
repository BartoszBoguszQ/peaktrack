<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workout extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'type',
        'duration_seconds',
        'distance_km',
        'calories',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'duration_seconds' => 'integer',
        'calories' => 'integer',
        'distance_km' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutExercises()
    {
        return $this->hasMany(\App\Models\WorkoutExercise::class)->orderBy('order_no');
    }
}
