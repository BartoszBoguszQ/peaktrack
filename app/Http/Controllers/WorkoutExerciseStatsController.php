<?php

namespace App\Http\Controllers;

use App\Models\WorkoutExercise;
use App\Services\Workout\WorkoutExerciseStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutExerciseStatsController extends Controller
{
    public function index(Request $request, WorkoutExerciseStatsService $statsService): Response
    {
        $exercisesPayload = $statsService->getExercisesSummaryForUser($request->user());

        return Inertia::render('Analytics/StrengthExercises', [
            'exercises' => $exercisesPayload,
        ]);
    }

    public function show(
        Request $request,
        WorkoutExercise $workoutExercise,
        WorkoutExerciseStatsService $statsService
    ): Response {
        $authenticatedUser = $request->user();

        $payload = $statsService->getExerciseSeriesForUser($authenticatedUser, $workoutExercise);

        return Inertia::render('Exercises/Stats', $payload);
    }
}
