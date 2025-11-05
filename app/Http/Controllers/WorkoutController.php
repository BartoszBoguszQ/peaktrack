<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Http\Request;
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

    public function show(Request $request, Workout $workout)
    {
        $authenticatedUser = $request->user();
        abort_unless($workout->user_id === $authenticatedUser->id, 404);

        $workout->load(['workoutExercises.sets', 'workoutExercises.exercise']);

        $workoutDetails = [
            'id' => $workout->id,
            'date' => $workout->date->toDateString(),
            'type' => $workout->type,
            'duration_seconds' => $workout->duration_seconds,
            'duration' => gmdate('H:i:s', $workout->duration_seconds),
            'distance_km' => (float) $workout->distance_km,
            'calories' => $workout->calories,
            'notes' => $workout->notes,
            'pace_min_per_km' => $workout->distance_km > 0 ? gmdate('i:s', (int) round($workout->duration_seconds / max(0.001, (float) $workout->distance_km))) : null,
            'speed_kmh' => $workout->duration_seconds > 0 ? round(((float) $workout->distance_km) / ($workout->duration_seconds / 3600), 2) : null,
            'exercises' => $workout->workoutExercises->map(function (\App\Models\WorkoutExercise $workoutExerciseModel) {
                return [
                    'id' => $workoutExerciseModel->id,
                    'order_no' => $workoutExerciseModel->order_no,
                    'name' => $workoutExerciseModel->name,
                    'exercise_id' => $workoutExerciseModel->exercise_id,
                    'external_source' => $workoutExerciseModel->external_source,
                    'external_id' => $workoutExerciseModel->external_id,
                    'catalog_name' => optional($workoutExerciseModel->exercise)->name,
                    'sets' => $workoutExerciseModel->sets->map(function (\App\Models\WorkoutSet $workoutSetModel) {
                        return [
                            'set_no' => $workoutSetModel->set_no,
                            'reps' => $workoutSetModel->reps,
                            'weight_kg' => $workoutSetModel->weight_kg,
                            'rir' => $workoutSetModel->rir,
                            'rest_seconds' => $workoutSetModel->rest_seconds,
                        ];
                    })->all(),
                ];
            })->all(),
        ];

        return Inertia::render('Workouts/Show', [
            'workout' => $workoutDetails,
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
}
