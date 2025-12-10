<?php

namespace App\Services\Workout;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Carbon;

class WorkoutDashboardService
{
    public function buildForUser(User $user): array
    {
        $recentWorkouts = Workout::where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(function (Workout $workoutModel) {
                return [
                    'id' => $workoutModel->id,
                    'date' => optional($workoutModel->date)->toDateString(),
                    'type' => $workoutModel->type,
                    'duration' => gmdate('H:i:s', (int) $workoutModel->duration_seconds),
                    'distance_km' => (float) $workoutModel->distance_km,
                    'calories' => $workoutModel->calories,
                ];
            })
            ->all();

        $statsPayload = [
            'workouts' => Workout::where('user_id', $user->id)->count(),
            'distance_km' => (float) Workout::where('user_id', $user->id)->sum('distance_km'),
            'calories' => (int) Workout::where('user_id', $user->id)->sum('calories'),
            'active_days' => Workout::where('user_id', $user->id)
                ->whereBetween('date', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()])
                ->distinct('date')
                ->count('date'),
        ];

        return [
            'stats' => $statsPayload,
            'recent' => $recentWorkouts,
        ];
    }
}
