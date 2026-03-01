<?php

namespace App\Services\Workout;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

class WorkoutService
{
    protected function normalizeName($value): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/u', ' ', $name);
        return (string) $name;
    }

    protected function normalizeExternalId($value): ?string
    {
        if ($value === null) return null;

        $externalId = trim((string) $value);
        return $externalId === '' ? null : $externalId;
    }

    protected function resolveSource(?string $source, ?string $externalId): string
    {
        if ($externalId !== null) {
            return 'api';
        }

        $source = $source !== null ? trim($source) : '';
        return $source === 'api' ? 'api' : 'manual';
    }

    protected function resolveExerciseId(User $user, string $name, string $source, ?string $externalId): ?int
    {
        if ($name === '') {
            return null;
        }

        if ($source === 'api' && $externalId !== null) {
            $exercise = Exercise::firstOrCreate(
                ['source' => 'api', 'external_id' => $externalId],
                ['user_id' => null, 'name' => $name]
            );

            if ($exercise->name !== $name) {
                $exercise->name = $name;
                $exercise->save();
            }

            return (int) $exercise->id;
        }

        $exercise = Exercise::firstOrCreate(
            ['user_id' => $user->id, 'source' => 'manual', 'name' => $name],
            ['external_id' => null]
        );

        return (int) $exercise->id;
    }

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
                foreach (($validatedData['exercises'] ?? []) as $exerciseIndex => $exercisePayload) {
                    $name = $this->normalizeName($exercisePayload['name'] ?? '');
                    $externalId = $this->normalizeExternalId($exercisePayload['external_id'] ?? null);
                    $source = $this->resolveSource($exercisePayload['source'] ?? null, $externalId);

                    $exerciseId = $exercisePayload['exercise_id'] ?? null;
                    if ($exerciseId) {
                        $source = 'manual';
                        $externalId = null;
                    }
                    if (!$exerciseId) {
                        $exerciseId = $this->resolveExerciseId($user, $name, $source, $externalId);
                    }

                    $workoutExercise = $workout->workoutExercises()->create([
                        'exercise_id' => $exerciseId ?: null,
                        'source' => $source,
                        'external_id' => $externalId,
                        'name' => $name,
                        'order_no' => $exercisePayload['order_no'] ?? $exerciseIndex + 1,
                    ]);

                    foreach (($exercisePayload['sets'] ?? []) as $setIndex => $setPayload) {
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

            $workout->workoutExercises()->each(function ($exerciseModel) {
                $exerciseModel->sets()->delete();
            });
            $workout->workoutExercises()->delete();

            if (($validatedData['type'] ?? null) === 'Strength') {
                foreach (($validatedData['exercises'] ?? []) as $exerciseIndex => $exercisePayload) {
                    $name = $this->normalizeName($exercisePayload['name'] ?? '');
                    $externalId = $this->normalizeExternalId($exercisePayload['external_id'] ?? null);
                    $source = $this->resolveSource($exercisePayload['source'] ?? null, $externalId);

                    $exerciseId = $exercisePayload['exercise_id'] ?? null;
                    if ($exerciseId) {
                        $source = 'manual';
                        $externalId = null;
                    }
                    if (!$exerciseId) {
                        $exerciseId = $this->resolveExerciseId($user, $name, $source, $externalId);
                    }

                    $workoutExercise = $workout->workoutExercises()->create([
                        'exercise_id' => $exerciseId ?: null,
                        'source' => $source,
                        'external_id' => $externalId,
                        'name' => $name,
                        'order_no' => $exercisePayload['order_no'] ?? $exerciseIndex + 1,
                    ]);

                    foreach (($exercisePayload['sets'] ?? []) as $setIndex => $setPayload) {
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
