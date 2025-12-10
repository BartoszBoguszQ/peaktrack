<?php

namespace App\Http\Controllers;

use App\Services\Workout\WorkoutDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, WorkoutDashboardService $dashboardService): Response
    {
        $payload = $dashboardService->buildForUser($request->user());

        return Inertia::render('Dashboard', $payload);
    }
}
