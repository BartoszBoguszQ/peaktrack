<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class WorkoutController extends Controller
{
    public function index(Request $request)
    {
        $authenticatedUser = $request->user();

        $paginatedWorkouts = Workout::where('user_id', $authenticatedUser->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->through(function (Workout $workoutModel) {
                return [
                    'id' => $workoutModel->id,
                    'date' => $workoutModel->date->toDateString(),
                    'type' => $workoutModel->type,
                    'duration' => gmdate('H:i:s', $workoutModel->duration_seconds),
                    'distance_km' => (float) $workoutModel->distance_km,
                    'calories' => $workoutModel->calories,
                ];
            });

        return Inertia::render('Workouts/Index', [
            'workouts' => $paginatedWorkouts,
        ]);
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '00:00:00';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    public function update(StoreWorkoutRequest $request, Workout $workout)
    {
        $user = $request->user();
        abort_unless($workout->user_id === $user->id, 403);

        $data = $request->validated();

        DB::transaction(function () use ($data, $workout, $user) {
            $workout->update([
                'user_id' => $user->id,
                'date' => $data['date'],
                'type' => $data['type'],
                'duration_seconds' => $data['duration_seconds'] ?? 0,
                'distance_km' => $data['distance_km'] ?? 0,
                'calories' => $data['calories'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $workout->workoutExercises()->delete();

            if (($data['type'] ?? null) === 'Strength' && !empty($data['exercises'])) {
                foreach ($data['exercises'] as $index => $exercisePayload) {
                    $exercise = $workout->workoutExercises()->create([
                        'exercise_id' => $exercisePayload['exercise_id'] ?? null,
                        'external_source' => $exercisePayload['external_source'] ?? null,
                        'external_id' => $exercisePayload['external_id'] ?? null,
                        'name' => $exercisePayload['name'] ?? '',
                        'order_no' => $index + 1,
                    ]);

                    foreach ($exercisePayload['sets'] ?? [] as $setIndex => $setPayload) {
                        $exercise->sets()->create([
                            'set_no' => $setPayload['set_no'] ?? $setIndex + 1,
                            'reps' => $setPayload['reps'] ?? null,
                            'weight_kg' => $setPayload['weight_kg'] ?? null,
                            'rir' => $setPayload['rir'] ?? null,
                            'rest_seconds' => $setPayload['rest_seconds'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('workouts.show', $workout)->with('status', 'Workout updated.');
    }

    public function create()
    {
        return Inertia::render('Workouts/Create');
    }

    public function store(StoreWorkoutRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['duration_seconds'] = $validatedData['duration_seconds'] ?? 0;
        $validatedData['distance_km'] = $validatedData['distance_km'] ?? 0;
        $validatedData['calories'] = $validatedData['calories'] ?? 0;

        $createdWorkout = Workout::create($validatedData + [
                'user_id' => $request->user()->id,
            ]);

        if (($validatedData['type'] ?? null) === 'Strength' && !empty($validatedData['exercises'])) {
            foreach ($validatedData['exercises'] as $exerciseOrderIndex => $exerciseInput) {
                $createdWorkoutExercise = WorkoutExercise::create([
                    'workout_id' => $createdWorkout->id,
                    'exercise_id' => $exerciseInput['exercise_id'] ?? null,
                    'external_source' => $exerciseInput['external_source'] ?? null,
                    'external_id' => $exerciseInput['external_id'] ?? null,
                    'name' => $exerciseInput['name'],
                    'order_no' => $exerciseInput['order_no'] ?? ($exerciseOrderIndex + 1),
                ]);

                foreach ($exerciseInput['sets'] ?? [] as $setOrderIndex => $setInput) {
                    WorkoutSet::create([
                        'workout_exercise_id' => $createdWorkoutExercise->id,
                        'set_no' => $setInput['set_no'] ?? ($setOrderIndex + 1),
                        'reps' => $setInput['reps'],
                        'weight_kg' => $setInput['weight_kg'] ?? null,
                        'rir' => $setInput['rir'] ?? null,
                        'rest_seconds' => $setInput['rest_seconds'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->route('workouts.show', $createdWorkout);
    }

    public function edit(Request $request, Workout $workout)
    {
        $user = $request->user();
        abort_unless($workout->user_id === $user->id, 403);

        $workout->load(['workoutExercises.sets']);

        $payload = [
            'id' => $workout->id,
            'date' => optional($workout->date)->format('Y-m-d'),
            'type' => $workout->type,
            'duration_seconds' => (int) $workout->duration_seconds,
            'distance_km' => (float) $workout->distance_km,
            'calories' => (int) $workout->calories,
            'notes' => $workout->notes,
            'exercises' => $workout->type === 'Strength'
                ? $workout->workoutExercises
                    ->map(function (WorkoutExercise $exercise) {
                        return [
                            'id' => $exercise->id,
                            'exercise_id' => $exercise->exercise_id,
                            'external_source' => $exercise->external_source,
                            'external_id' => $exercise->external_id,
                            'name' => $exercise->name,
                            'order_no' => $exercise->order_no,
                            'sets' => $exercise->sets
                                ->map(function (WorkoutSet $set) {
                                    return [
                                        'id' => $set->id,
                                        'set_no' => $set->set_no,
                                        'reps' => $set->reps,
                                        'weight_kg' => $set->weight_kg,
                                        'rir' => $set->rir,
                                        'rest_seconds' => $set->rest_seconds,
                                    ];
                                })
                                ->values()
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all()
                : [],
        ];

        return Inertia::render('Workouts/Edit', [
            'workout' => $payload,
        ]);
    }

    public function show(Request $request, Workout $workout)
    {
        $user = $request->user();
        abort_unless($workout->user_id === $user->id, 403);

        $workout->load(['workoutExercises.sets']);

        $payload = [
            'id' => $workout->id,
            'date' => optional($workout->date)->format('Y-m-d'),
            'type' => $workout->type,
            'duration_seconds' => (int) $workout->duration_seconds,
            'duration' => $this->formatDuration((int) $workout->duration_seconds),
            'distance_km' => (float) $workout->distance_km,
            'calories' => (int) $workout->calories,
            'notes' => $workout->notes,
            'exercises' => $workout->type === 'Strength'
                ? $workout->workoutExercises
                    ->map(function (WorkoutExercise $exercise) {
                        return [
                            'id' => $exercise->id,
                            'exercise_id' => $exercise->exercise_id,
                            'external_source' => $exercise->external_source,
                            'external_id' => $exercise->external_id,
                            'name' => $exercise->name,
                            'order_no' => $exercise->order_no,
                            'sets' => $exercise->sets
                                ->map(function (WorkoutSet $set) {
                                    return [
                                        'id' => $set->id,
                                        'set_no' => $set->set_no,
                                        'reps' => $set->reps,
                                        'weight_kg' => $set->weight_kg,
                                        'rir' => $set->rir,
                                        'rest_seconds' => $set->rest_seconds,
                                    ];
                                })
                                ->values()
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all()
                : [],
        ];

        return Inertia::render('Workouts/Show', [
            'workout' => $payload,
        ]);
    }

    public function dashboard(Request $request)
    {
        $authenticatedUser = $request->user();

        $recentWorkouts = Workout::where('user_id', $authenticatedUser->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(function (Workout $workoutModel) {
                return [
                    'id' => $workoutModel->id,
                    'date' => $workoutModel->date->toDateString(),
                    'type' => $workoutModel->type,
                    'duration' => gmdate('H:i:s', $workoutModel->duration_seconds),
                    'distance_km' => (float) $workoutModel->distance_km,
                    'calories' => $workoutModel->calories,
                ];
            })->all();

        $statsPayload = [
            'workouts' => Workout::where('user_id', $authenticatedUser->id)->count(),
            'distance_km' => (float) Workout::where('user_id', $authenticatedUser->id)->sum('distance_km'),
            'calories' => (int) Workout::where('user_id', $authenticatedUser->id)->sum('calories'),
            'active_days' => Workout::where('user_id', $authenticatedUser->id)
                ->whereBetween('date', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()])
                ->distinct('date')
                ->count('date'),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $statsPayload,
            'recent' => $recentWorkouts,
        ]);
    }

    public function destroy(Request $request, Workout $workout)
    {
        $user = $request->user();
        abort_unless($workout->user_id === $user->id, 403);

        $workout->delete();

        return redirect()->route('workouts.index')->with('status', 'Workout deleted.');
    }

    public function analytics(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $fromWeekly = $today->copy()->subWeeks(7)->startOfWeek();
        $fromMonthly = $today->copy()->subMonths(5)->startOfMonth();

        $weeklySource = Workout::where('user_id', $user->id)
            ->where('date', '>=', $fromWeekly)
            ->get();

        $weekly = $weeklySource
            ->groupBy(function (Workout $w) {
                return optional($w->date)->startOfWeek()->toDateString();
            })
            ->map(function ($group, $key) {
                $date = Carbon::parse($key);
                return [
                    'week_start' => $key,
                    'label' => $date->format('d.m'),
                    'workouts' => $group->count(),
                    'distance_km' => (float) $group->sum('distance_km'),
                    'duration_seconds' => (int) $group->sum('duration_seconds'),
                    'calories' => (int) $group->sum('calories'),
                ];
            })
            ->values()
            ->sortBy('week_start')
            ->values()
            ->all();

        $monthlySource = Workout::where('user_id', $user->id)
            ->where('date', '>=', $fromMonthly)
            ->get();

        $monthly = $monthlySource
            ->groupBy(function (Workout $w) {
                return optional($w->date)->startOfMonth()->toDateString();
            })
            ->map(function ($group, $key) {
                $date = Carbon::parse($key);
                return [
                    'month_start' => $key,
                    'label' => $date->format('m.Y'),
                    'workouts' => $group->count(),
                    'distance_km' => (float) $group->sum('distance_km'),
                    'duration_seconds' => (int) $group->sum('duration_seconds'),
                    'calories' => (int) $group->sum('calories'),
                ];
            })
            ->values()
            ->sortBy('month_start')
            ->values()
            ->all();

        return Inertia::render('Analytics/Overview', [
            'weekly' => $weekly,
            'monthly' => $monthly,
        ]);
    }

    protected function estimateOneRepMax(?float $weight, ?int $reps): float
    {
        if (!$weight || !$reps || $reps <= 0) {
            return 0.0;
        }

        return round($weight * (1 + $reps / 30), 1);
    }

    public function records(Request $request)
    {
        $user = $request->user();

        $allWorkouts = Workout::where('user_id', $user->id)
            ->where('duration_seconds', '>', 0)
            ->get();

        $runWorkouts = $allWorkouts->where('type', 'Run')->where('distance_km', '>', 0);
        $rideWorkouts = $allWorkouts->where('type', 'Ride')->where('distance_km', '>', 0);
        $swimWorkouts = $allWorkouts->where('type', 'Swim')->where('distance_km', '>', 0);

        $bestForDistance = function ($workoutsCollection, float $targetDistanceKm, float $tolerance = 0.1): ?array {
            if ($workoutsCollection->isEmpty()) {
                return null;
            }

            $minimumDistance = $targetDistanceKm * (1 - $tolerance);
            $maximumDistance = $targetDistanceKm * (1 + $tolerance);

            $candidateWorkout = $workoutsCollection
                ->filter(function (Workout $workoutModel) use ($minimumDistance, $maximumDistance) {
                    return $workoutModel->distance_km >= $minimumDistance && $workoutModel->distance_km <= $maximumDistance;
                })
                ->sortBy('duration_seconds')
                ->first();

            if (! $candidateWorkout) {
                return null;
            }

            $paceSecondsPerKm = $candidateWorkout->distance_km > 0
                ? (int) round($candidateWorkout->duration_seconds / max($candidateWorkout->distance_km, 0.0001))
                : null;

            return [
                'workout_id' => $candidateWorkout->id,
                'date' => optional($candidateWorkout->date)->toDateString(),
                'distance_km' => (float) $candidateWorkout->distance_km,
                'duration_seconds' => (int) $candidateWorkout->duration_seconds,
                'pace_seconds_per_km' => $paceSecondsPerKm,
            ];
        };

        $runLongestWorkout = $runWorkouts->sortByDesc('distance_km')->first();
        $runFastestOverallWorkout = $runWorkouts
            ->filter(function (Workout $workoutModel) {
                return $workoutModel->duration_seconds > 0 && $workoutModel->distance_km > 0;
            })
            ->sortBy(function (Workout $workoutModel) {
                return $workoutModel->duration_seconds / max($workoutModel->distance_km, 0.0001);
            })
            ->first();

        $rideLongestWorkout = $rideWorkouts->sortByDesc('distance_km')->first();
        $swimLongestWorkout = $swimWorkouts->sortByDesc('distance_km')->first();

        $endurance = [
            'run' => [
                'longest' => $runLongestWorkout ? [
                    'workout_id' => $runLongestWorkout->id,
                    'date' => optional($runLongestWorkout->date)->toDateString(),
                    'distance_km' => (float) $runLongestWorkout->distance_km,
                    'duration_seconds' => (int) $runLongestWorkout->duration_seconds,
                ] : null,
                'fastest_overall' => $runFastestOverallWorkout ? [
                    'workout_id' => $runFastestOverallWorkout->id,
                    'date' => optional($runFastestOverallWorkout->date)->toDateString(),
                    'distance_km' => (float) $runFastestOverallWorkout->distance_km,
                    'duration_seconds' => (int) $runFastestOverallWorkout->duration_seconds,
                    'pace_seconds_per_km' => (int) round(
                        $runFastestOverallWorkout->duration_seconds / max($runFastestOverallWorkout->distance_km, 0.0001)
                    ),
                ] : null,
                'best_400m' => $bestForDistance($runWorkouts, 0.4, 0.15),
                'best_1k' => $bestForDistance($runWorkouts, 1.0, 0.1),
                'best_mile' => $bestForDistance($runWorkouts, 1.609, 0.1),
                'best_5k' => $bestForDistance($runWorkouts, 5.0, 0.1),
                'best_10k' => $bestForDistance($runWorkouts, 10.0, 0.1),
            ],
            'ride' => [
                'longest' => $rideLongestWorkout ? [
                    'workout_id' => $rideLongestWorkout->id,
                    'date' => optional($rideLongestWorkout->date)->toDateString(),
                    'distance_km' => (float) $rideLongestWorkout->distance_km,
                    'duration_seconds' => (int) $rideLongestWorkout->duration_seconds,
                ] : null,
                'best_5k' => $bestForDistance($rideWorkouts, 5.0, 0.2),
                'best_20k' => $bestForDistance($rideWorkouts, 20.0, 0.2),
                'best_40k' => $bestForDistance($rideWorkouts, 40.0, 0.2),
            ],
            'swim' => [
                'longest' => $swimLongestWorkout ? [
                    'workout_id' => $swimLongestWorkout->id,
                    'date' => optional($swimLongestWorkout->date)->toDateString(),
                    'distance_km' => (float) $swimLongestWorkout->distance_km,
                    'duration_seconds' => (int) $swimLongestWorkout->duration_seconds,
                ] : null,
                'best_100m' => $bestForDistance($swimWorkouts, 0.1, 0.2),
                'best_500m' => $bestForDistance($swimWorkouts, 0.5, 0.2),
                'best_1000m' => $bestForDistance($swimWorkouts, 1.0, 0.2),
            ],
        ];

        $strengthExercises = WorkoutExercise::whereHas('workout', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['sets', 'exercise', 'workout'])
            ->get();

        $groupedByIdentity = $strengthExercises->groupBy(function (WorkoutExercise $exerciseModel) {
            if ($exerciseModel->exercise_id) {
                return 'local:' . $exerciseModel->exercise_id;
            }

            if ($exerciseModel->external_source && $exerciseModel->external_id) {
                return 'ext:' . $exerciseModel->external_source . ':' . $exerciseModel->external_id;
            }

            return 'name:' . mb_strtolower($exerciseModel->name);
        });

        $strength = $groupedByIdentity
            ->map(function ($exerciseGroup) {
                $firstExercise = $exerciseGroup->first();
                $exerciseName = $firstExercise ? $firstExercise->name : 'Exercise';

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

                $bestWeightKg = $bestByWeightRow ? (float) $bestByWeightRow['set']->weight_kg : 0;
                $bestOneRepMaxKg = $bestByWeightRow
                    ? $this->estimateOneRepMax(
                        (float) $bestByWeightRow['set']->weight_kg,
                        (int) $bestByWeightRow['set']->reps
                    )
                    : 0;

                $totalVolumeKg = $allSetsForExercise->sum(function (array $row) {
                    $setModel = $row['set'];
                    return (float) ($setModel->weight_kg ?? 0) * (int) ($setModel->reps ?? 0);
                });

                return [
                    'name' => $exerciseName,
                    'best_weight_kg' => $bestWeightKg,
                    'best_one_rm_kg' => $bestOneRepMaxKg,
                    'total_volume' => $totalVolumeKg,
                ];
            })
            ->filter()
            ->sortByDesc('best_one_rm_kg')
            ->values()
            ->all();

        return Inertia::render('Analytics/Records', [
            'endurance' => $endurance,
            'strength' => $strength,
        ]);
    }
}
