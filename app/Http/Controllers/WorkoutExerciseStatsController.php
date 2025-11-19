<?php

namespace App\Http\Controllers;

use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class WorkoutExerciseStatsController extends Controller
{
    protected function estimateOneRepMax(?float $weightKilograms, ?int $repetitions): float
    {
        if (! $weightKilograms || ! $repetitions || $repetitions <= 0) {
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

    public function index(Request $request)
    {
        $authenticatedUser = $request->user();

        $strengthExercises = WorkoutExercise::whereHas('workout', function ($query) use ($authenticatedUser) {
            $query->where('user_id', $authenticatedUser->id);
        })
            ->with(['sets', 'workout'])
            ->get();

        $groupedByIdentity = $strengthExercises->groupBy(function (WorkoutExercise $exerciseModel) {
            return $this->getExerciseIdentityKey($exerciseModel);
        });

        $exercisesPayload = $groupedByIdentity
            ->map(function ($exerciseGroup) {
                $firstExercise = $exerciseGroup->first();
                if (! $firstExercise) {
                    return null;
                }

                $exerciseName = $firstExercise->name ?: 'Exercise';

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
                $bestOneRepMaxKilograms = $bestByWeightRow
                    ? $this->estimateOneRepMax(
                        (float) $bestByWeightRow['set']->weight_kg,
                        (int) $bestByWeightRow['set']->reps
                    )
                    : 0.0;

                $totalVolumeKilograms = $allSetsForExercise->sum(function (array $row) {
                    $setModel = $row['set'];
                    return (float) ($setModel->weight_kg ?? 0) * (int) ($setModel->reps ?? 0);
                });

                $workoutDates = $exerciseGroup->map(function (WorkoutExercise $exerciseModel) {
                    return optional($exerciseModel->workout->date);
                })->filter();

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

        return Inertia::render('Analytics/StrengthExercises', [
            'exercises' => $exercisesPayload,
        ]);
    }

    public function show(Request $request, WorkoutExercise $workoutExercise)
    {
        $authenticatedUser = $request->user();

        if ($workoutExercise->workout->user_id !== $authenticatedUser->id) {
            abort(403);
        }

        $exerciseIdentityKey = $this->getExerciseIdentityKey($workoutExercise);

        $allExercisesWithSameIdentity = WorkoutExercise::whereHas('workout', function ($query) use ($authenticatedUser) {
            $query->where('user_id', $authenticatedUser->id);
        })
            ->where(function ($query) use ($exerciseIdentityKey) {
                $query->whereNotNull('id');
            })
            ->with(['sets', 'workout'])
            ->get()
            ->filter(function (WorkoutExercise $exerciseModel) use ($exerciseIdentityKey) {
                return $this->getExerciseIdentityKey($exerciseModel) === $exerciseIdentityKey;
            });

        $exerciseName = $allExercisesWithSameIdentity->first()
            ? $allExercisesWithSameIdentity->first()->name
            : $workoutExercise->name;

        $groupedByDate = $allExercisesWithSameIdentity->groupBy(function (WorkoutExercise $exerciseModel) {
            return optional($exerciseModel->workout->date)
                ? $exerciseModel->workout->date->toDateString()
                : null;
        });

        $series = $groupedByDate
            ->filter(function ($group, $dateKey) {
                return $dateKey !== null;
            })
            ->map(function ($exerciseGroup, $dateKey) {
                $allSetsForDate = $exerciseGroup->flatMap(function (WorkoutExercise $exerciseModel) {
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
        ];

        return Inertia::render('Exercises/Stats', [
            'exercise' => $exercisePayload,
            'series' => $series,
        ]);
    }
}
