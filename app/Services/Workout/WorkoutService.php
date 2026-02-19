<?php

namespace App\Services\Workout;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Support\Facades\DB;

class WorkoutService
{
    public function createWorkout(User $user, array $validatedData): Workout
    {
        return DB::transaction(function () use ($user, $validatedData) {
            $workout = Workout::create([
                'user_id' => $user->id,
                'date' => $validatedData['date'],
                'type' => $validatedData['type'],
                'duration_seconds' => $validatedData['duration_seconds'] ?? 0,
                'distance_km' => $validatedData['distance_km'] ?? 0,
                'calories' => $validatedData['calories'] ?? 0,
                'notes' => $validatedData['notes'] ?? null,
            ]);

            if (($validatedData['type'] ?? null) === 'Strength') {
                foreach ($validatedData['exercises'] ?? [] as $exerciseIndex => $exercisePayload) {
                    $workoutExercise = $workout->workoutExercises()->create([
                        'exercise_id' => $exercisePayload['exercise_id'] ?? null,
                        'external_source' => $exercisePayload['external_source'] ?? null,
                        'external_id' => $exercisePayload['external_id'] ?? null,
                        'name' => $exercisePayload['name'] ?? '',
                        'order_no' => $exercisePayload['order_no'] ?? $exerciseIndex + 1,
                    ]);

                    foreach ($exercisePayload['sets'] ?? [] as $setIndex => $setPayload) {
                        $workoutExercise->sets()->create([
                            'set_no' => $setPayload['set_no'] ?? $setIndex + 1,
                            'reps' => $setPayload['reps'] ?? null,
                            'weight_kg' => $setPayload['weight_kg'] ?? null,
                            'rir' => $setPayload['rir'] ?? null,
                            'rest_seconds' => $setPayload['rest_seconds'] ?? null,
                        ]);
                    }
                }
            }

            return $workout;
        });
    }

    public function updateWorkout(Workout $workout, User $user, array $validatedData): Workout
    {
        return DB::transaction(function () use ($workout, $user, $validatedData) {
            $workout->update([
                'user_id' => $user->id,
                'date' => $validatedData['date'],
                'type' => $validatedData['type'],
                'duration_seconds' => $validatedData['duration_seconds'] ?? 0,
                'distance_km' => $validatedData['distance_km'] ?? 0,
                'calories' => $validatedData['calories'] ?? 0,
                'notes' => $validatedData['notes'] ?? null,
            ]);

            $workout->workoutExercises()->each(function (WorkoutExercise $exerciseModel) {
                $exerciseModel->sets()->delete();
            });
            $workout->workoutExercises()->delete();

            if (($validatedData['type'] ?? null) === 'Strength') {
                foreach ($validatedData['exercises'] ?? [] as $exerciseIndex => $exercisePayload) {
                    $workoutExercise = $workout->workoutExercises()->create([
                        'exercise_id' => $exercisePayload['exercise_id'] ?? null,
                        'external_source' => $exercisePayload['external_source'] ?? null,
                        'external_id' => $exercisePayload['external_id'] ?? null,
                        'name' => $exercisePayload['name'] ?? '',
                        'order_no' => $exercisePayload['order_no'] ?? $exerciseIndex + 1,
                    ]);

                    foreach ($exercisePayload['sets'] ?? [] as $setIndex => $setPayload) {
                        $workoutExercise->sets()->create([
                            'set_no' => $setPayload['set_no'] ?? $setIndex + 1,
                            'reps' => $setPayload['reps'] ?? null,
                            'weight_kg' => $setPayload['weight_kg'] ?? null,
                            'rir' => $setPayload['rir'] ?? null,
                            'rest_seconds' => $setPayload['rest_seconds'] ?? null,
                        ]);
                    }
                }
            }

            return $workout->fresh(['workoutExercises.sets']);
        });
    }
}
