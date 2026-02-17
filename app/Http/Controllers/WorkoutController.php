<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkoutRequest;
use App\Http\Resources\WorkoutDetailResource;
use App\Http\Resources\WorkoutResource;
use App\Models\Workout;
use App\Services\Workout\WorkoutCrudService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    public function index(Request $request): Response
    {
        $authenticatedUser = $request->user();

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'type' => ['nullable', 'in:run,strength,swim,ride,other'],
            'min_distance_km' => ['nullable', 'numeric', 'min:0'],
            'max_distance_km' => ['nullable', 'numeric', 'min:0'],
            'min_calories' => ['nullable', 'integer', 'min:0'],
            'max_calories' => ['nullable', 'integer', 'min:0'],
        ]);

        $workoutsQuery = Workout::query()
            ->where('user_id', $authenticatedUser->id);

        if (! empty($validated['date_from'])) {
            $dateFrom = Carbon::parse($validated['date_from'])->toDateString();
            $workoutsQuery->whereDate('date', '>=', $dateFrom);
        }

        if (! empty($validated['date_to'])) {
            $dateTo = Carbon::parse($validated['date_to'])->toDateString();
            $workoutsQuery->whereDate('date', '<=', $dateTo);
        }

        if (! empty($validated['type'])) {
            $selectedType = strtolower((string) $validated['type']);
            $workoutsQuery->whereRaw('LOWER(type) = ?', [$selectedType]);
        }

        if (null !== ($validated['min_distance_km'] ?? null)) {
            $workoutsQuery->where('distance_km', '>=', (float) $validated['min_distance_km']);
        }

        if (null !== ($validated['max_distance_km'] ?? null)) {
            $workoutsQuery->where('distance_km', '<=', (float) $validated['max_distance_km']);
        }

        if (null !== ($validated['min_calories'] ?? null)) {
            $workoutsQuery->where('calories', '>=', (int) $validated['min_calories']);
        }

        if (null !== ($validated['max_calories'] ?? null)) {
            $workoutsQuery->where('calories', '<=', (int) $validated['max_calories']);
        }

        $paginatedWorkouts = $workoutsQuery
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($validated)
            ->through(fn (Workout $workoutModel) => (new WorkoutResource($workoutModel))->toArray($request));

        return Inertia::render('Workouts/Index', [
            'workouts' => $paginatedWorkouts,
            'filters' => $validated,
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
