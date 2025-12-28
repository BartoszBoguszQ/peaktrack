<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Resources\WorkoutDetailResource;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\Workout\WorkoutCrudService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    public function index(Request $request): Response
    {
        $authenticatedUser = $request->user();

        $paginatedWorkouts = Workout::where('user_id', $authenticatedUser->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->through(fn (Workout $workoutModel) => (new WorkoutResource($workoutModel))->toArray($request));

        return Inertia::render('Workouts/Index', [
            'workouts' => $paginatedWorkouts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Workouts/Create');
    }

    public function store(StoreWorkoutRequest $request, WorkoutCrudService $workoutCrudService)
    {
        $workout = $workoutCrudService->createWorkout($request->user(), $request->validated());

        return redirect()
            ->route('workouts.show', $workout)
            ->with('success', 'Workout created.');
    }

    public function show(Request $request, Workout $workout): Response
    {
        $authenticatedUser = $request->user();
        abort_unless($workout->user_id === $authenticatedUser->id, 403);

        $workout->load(['workoutExercises.sets']);

        return Inertia::render('Workouts/Show', [
            'workout' => (new WorkoutDetailResource($workout))->toArray($request),
        ]);
    }

    public function edit(Request $request, Workout $workout): Response
    {
        $authenticatedUser = $request->user();
        abort_unless($workout->user_id === $authenticatedUser->id, 403);

        $workout->load(['workoutExercises.sets']);

        return Inertia::render('Workouts/Edit', [
            'workout' => (new WorkoutDetailResource($workout))->toArray($request),
        ]);
    }

    public function update(StoreWorkoutRequest $request, Workout $workout, WorkoutCrudService $workoutCrudService)
    {
        $authenticatedUser = $request->user();
        abort_unless($workout->user_id === $authenticatedUser->id, 403);

        $updatedWorkout = $workoutCrudService->updateWorkout($workout, $authenticatedUser, $request->validated());

        return redirect()
            ->route('workouts.show', $updatedWorkout)
            ->with('success', 'Workout updated.');
    }

    public function destroy(Request $request, Workout $workout)
    {
        $authenticatedUser = $request->user();
        abort_unless($workout->user_id === $authenticatedUser->id, 403);

        $workout->delete();

        return redirect()
            ->route('workouts.index')
            ->with('success', 'Workout deleted.');
    }
}
