<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutAnalyticsRequest;
use App\Http\Requests\WorkoutRecordsRequest;
use App\Services\Workout\WorkoutAnalyticsService;
use App\Services\Workout\WorkoutExerciseStatsService;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutAnalyticsController extends Controller
{
    public function index(WorkoutAnalyticsRequest $request, WorkoutAnalyticsService $analyticsService): Response
    {
        $user = $request->user();
        $selectedTypes = $request->selectedTypes();
        $dateRange = $request->dateRange();

        $payload = $analyticsService->buildOverviewForUser($user, $selectedTypes, $dateRange);

        return Inertia::render('Analytics/Overview', $payload);
    }

    public function records(
        WorkoutRecordsRequest $request,
        WorkoutAnalyticsService $analyticsService,
        WorkoutExerciseStatsService $strengthService
    ): Response {
        $user = $request->user();

        $payload = $analyticsService->buildRecordsForUser($user, $strengthService);

        return Inertia::render('Analytics/Records', $payload);
    }
}
