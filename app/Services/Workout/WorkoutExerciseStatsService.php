<?php

namespace App\Services\Workout;

use App\Models\User;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WorkoutExerciseStatsService
{
    protected function estimateOneRepMax(?float $weightKilograms, ?int $repetitions): float
    {
        if (!$weightKilograms || !$repetitions || $repetitions <= 0) {
            return 0.0;
        }

        return round($weightKilograms * (1 + $repetitions / 30), 1);
    }

    protected function getExerciseIdentityKey(WorkoutExercise $exerciseModel): string
    {
        if ($exerciseModel->exercise_id) {
            return 'local:' . $exerciseModel->exercise_id;
        }

        if ($exerciseModel->external_source && $exerciseModel->external_id) {
            return 'external:' . $exerciseModel->external_source . ':' . $exerciseModel->external_id;
        }

        return 'name:' . mb_strtolower($exerciseModel->name);
    }

    public function getExercisesSummaryForUser(User $user): array
    {
        $strengthExercises = WorkoutExercise::whereHas('workout', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['sets', 'workout', 'exercise'])
            ->get();

        $groupedByIdentity = $strengthExercises->groupBy(function (WorkoutExercise $exerciseModel) {
            return $this->getExerciseIdentityKey($exerciseModel);
        });

        $exercisesPayload = $groupedByIdentity
            ->map(function (Collection $exerciseGroup) {
                $firstExercise = $exerciseGroup->first();
                if (!$firstExercise) {
                    return null;
                }

                $exerciseName = $firstExercise->name ?: optional($firstExercise->exercise)->name;
                if (!$exerciseName) {
                    return null;
                }

                $allSetsForExercise = $exerciseGroup->flatMap(function (WorkoutExercise $exerciseModel) {
                    return $exerciseModel->sets->map(function (WorkoutSet $setModel) use ($exerciseModel) {
                        return [
                            'set' => $setModel,
                            'exercise' => $exerciseModel,
                        ];
                    });
                });

                if ($allSetsForExercise->isEmpty()) {
                    return null;
                }

                $bestByWeightRow = $allSetsForExercise
                    ->filter(function (array $row) {
                        return $row['set']->weight_kg !== null;
                    })
                    ->sortByDesc(function (array $row) {
                        return $row['set']->weight_kg;
                    })
                    ->first();

                $bestWeightKilograms = $bestByWeightRow ? (float) $bestByWeightRow['set']->weight_kg : 0.0;

                $bestOneRepRow = $allSetsForExercise
                    ->filter(function (array $row) {
                        return $row['set']->weight_kg !== null;
                    })
                    ->sortByDesc(function (array $row) {
                        return $this->estimateOneRepMax(
                            (float) $row['set']->weight_kg,
                            (int) $row['set']->reps
                        );
                    })
                    ->first();

                $bestOneRepMaxKilograms = $bestOneRepRow
                    ? $this->estimateOneRepMax(
                        (float) $bestOneRepRow['set']->weight_kg,
                        (int) $bestOneRepRow['set']->reps
                    )
                    : 0.0;

                $totalVolumeKilograms = $allSetsForExercise
                    ->filter(function (array $row) {
                        return $row['set']->weight_kg !== null && $row['set']->reps !== null;
                    })
                    ->sum(function (array $row) {
                        return (float) $row['set']->weight_kg * (int) $row['set']->reps;
                    });

                $workoutDates = $exerciseGroup
                    ->map(function (WorkoutExercise $exerciseModel) {
                        return optional(optional($exerciseModel->workout)->date);
                    })
                    ->filter();

                $sessionsCount = $exerciseGroup
                    ->pluck('workout_id')
                    ->unique()
                    ->count();

                $lastDate = $workoutDates->isNotEmpty()
                    ? $workoutDates->max()->toDateString()
                    : null;

                return [
                    'name' => $exerciseName,
                    'representative_workout_exercise_id' => $firstExercise->id,
                    'sessions_count' => $sessionsCount,
                    'last_date' => $lastDate,
                    'best_weight_kg' => $bestWeightKilograms,
                    'best_one_rm_kg' => $bestOneRepMaxKilograms,
                    'total_volume' => $totalVolumeKilograms,
                ];
            })
            ->filter()
            ->sortByDesc('best_one_rm_kg')
            ->values()
            ->all();

        return $exercisesPayload;
    }

    public function getExerciseSeriesForUser(User $user, WorkoutExercise $workoutExercise): array
    {
        if ($workoutExercise->workout->user_id !== $user->id) {
            abort(403);
        }

        $exerciseIdentityKey = $this->getExerciseIdentityKey($workoutExercise);

        $allExercisesWithSameIdentity = WorkoutExercise::whereHas('workout', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->when($workoutExercise->exercise_id, function ($query) use ($workoutExercise) {
                $query->where('exercise_id', $workoutExercise->exercise_id);
            }, function ($query) use ($workoutExercise) {
                if ($workoutExercise->external_source && $workoutExercise->external_id) {
                    $query->where('external_source', $workoutExercise->external_source)
                        ->where('external_id', $workoutExercise->external_id);
                } else {
                    $query->where('name', $workoutExercise->name);
                }
            })
            ->with(['sets', 'workout', 'exercise'])
            ->get();

        $exerciseName = $workoutExercise->name ?: optional($workoutExercise->exercise)->name;

        $groupedByDate = $allExercisesWithSameIdentity->groupBy(function (WorkoutExercise $exerciseModel) {
            $date = optional($exerciseModel->workout)->date;
            if ($date instanceof Carbon) {
                return $date->toDateString();
            }

            if ($date) {
                return Carbon::parse($date)->toDateString();
            }

            return null;
        });

        $series = $groupedByDate
            ->map(function (Collection $exerciseGroupForDate, $dateKey) {
                if ($dateKey === null) {
                    return null;
                }

                $allSetsForDate = $exerciseGroupForDate->flatMap(function (WorkoutExercise $exerciseModel) {
                    return $exerciseModel->sets;
                });

                if ($allSetsForDate->isEmpty()) {
                    return null;
                }

                $totalVolumeForDate = $allSetsForDate->sum(function (WorkoutSet $setModel) {
                    return (float) ($setModel->weight_kg ?? 0) * (int) ($setModel->reps ?? 0);
                });

                $bestSetForDate = $allSetsForDate
                    ->filter(function (WorkoutSet $setModel) {
                        return $setModel->weight_kg !== null;
                    })
                    ->sortByDesc(function (WorkoutSet $setModel) {
                        return $setModel->weight_kg;
                    })
                    ->first();

                $bestEstimatedOneRepMax = $bestSetForDate
                    ? $this->estimateOneRepMax(
                        (float) $bestSetForDate->weight_kg,
                        (int) $bestSetForDate->reps
                    )
                    : 0.0;

                return [
                    'date' => $dateKey,
                    'volume' => $totalVolumeForDate,
                    'est_one_rm_kg' => $bestEstimatedOneRepMax,
                ];
            })
            ->filter()
            ->sortBy('date')
            ->values()
            ->all();

        $exercisePayload = [
            'id' => $workoutExercise->id,
            'name' => $exerciseName,
            'identity_key' => $exerciseIdentityKey,
        ];

        return [
            'exercise' => $exercisePayload,
            'series' => $series,
        ];
    }
}
