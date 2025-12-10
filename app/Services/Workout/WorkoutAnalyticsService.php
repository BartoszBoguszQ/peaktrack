<?php

namespace App\Services\Workout;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WorkoutAnalyticsService
{
    public function buildOverviewForUser(
        User $user,
        ?array $requestedTypes = null,
        ?array $dateRange = null
    ): array {
        $availableTypes = Workout::where('user_id', $user->id)
            ->whereNotNull('type')
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->values()
            ->all();

        if ($requestedTypes === null) {
            $selectedTypes = $availableTypes;
        } else {
            $selectedTypes = array_values(array_intersect($availableTypes, $requestedTypes));
        }

        $workoutsQuery = Workout::where('user_id', $user->id)
            ->whereNotNull('date');

        if (!empty($selectedTypes)) {
            $workoutsQuery->whereIn('type', $selectedTypes);
        }

        if ($dateRange !== null) {
            $from = $dateRange['from'] ?? null;
            $to = $dateRange['to'] ?? null;

            if ($from) {
                $fromCarbon = Carbon::parse($from)->startOfDay();
                $workoutsQuery->where('date', '>=', $fromCarbon);
            }

            if ($to) {
                $toCarbon = Carbon::parse($to)->endOfDay();
                $workoutsQuery->where('date', '<=', $toCarbon);
            }
        }

        $workouts = $workoutsQuery->get();

        [$weekly, $monthly] = $this->buildAnalyticsStats($workouts);

        return [
            'weekly' => $weekly,
            'monthly' => $monthly,
            'filters' => [
                'available_types' => $availableTypes,
                'selected_types' => $selectedTypes,
            ],
        ];
    }

    protected function buildAnalyticsStats(Collection $workouts): array
    {
        $now = Carbon::now();

        $weekly = [];
        for ($weekOffset = 7; $weekOffset >= 0; $weekOffset--) {
            $start = $now->copy()->startOfWeek()->subWeeks($weekOffset);
            $end = $start->copy()->endOfWeek();

            $rangeWorkouts = $workouts->filter(function (Workout $workout) use ($start, $end) {
                if (!$workout->date) {
                    return false;
                }
                $date = $workout->date instanceof Carbon ? $workout->date : Carbon::parse($workout->date);
                return $date->between($start, $end);
            });

            $weekly[] = [
                'label' => $start->format('d.m') . ' - ' . $end->format('d.m'),
                'workouts' => $rangeWorkouts->count(),
                'distance_km' => (float) $rangeWorkouts->sum('distance_km'),
                'calories' => (int) $rangeWorkouts->sum('calories'),
            ];
        }

        $monthly = [];
        for ($monthOffset = 11; $monthOffset >= 0; $monthOffset--) {
            $start = $now->copy()->startOfMonth()->subMonths($monthOffset);
            $end = $start->copy()->endOfMonth();

            $rangeWorkouts = $workouts->filter(function (Workout $workout) use ($start, $end) {
                if (!$workout->date) {
                    return false;
                }
                $date = $workout->date instanceof Carbon ? $workout->date : Carbon::parse($workout->date);
                return $date->between($start, $end);
            });

            $monthly[] = [
                'label' => $start->format('m.Y'),
                'workouts' => $rangeWorkouts->count(),
                'distance_km' => (float) $rangeWorkouts->sum('distance_km'),
                'calories' => (int) $rangeWorkouts->sum('calories'),
            ];
        }

        return [$weekly, $monthly];
    }

    public function buildRecordsForUser(User $user, WorkoutExerciseStatsService $strengthService): array
    {
        $allWorkouts = Workout::where('user_id', $user->id)
            ->where('duration_seconds', '>', 0)
            ->get();

        $endurance = [
            'run' => $this->buildEnduranceRecordsForType($allWorkouts, 'Run'),
            'ride' => $this->buildEnduranceRecordsForType($allWorkouts, 'Ride'),
            'swim' => $this->buildEnduranceRecordsForType($allWorkouts, 'Swim'),
        ];

        $maxCaloriesWorkout = $allWorkouts
            ->filter(function (Workout $workoutModel) {
                return $workoutModel->calories !== null && $workoutModel->calories > 0;
            })
            ->sortByDesc('calories')
            ->first();

        $endurance['overall'] = [
            'max_calories' => $maxCaloriesWorkout ? [
                'workout_id' => $maxCaloriesWorkout->id,
                'date' => optional($maxCaloriesWorkout->date)->toDateString(),
                'type' => $maxCaloriesWorkout->type,
                'distance_km' => (float) $maxCaloriesWorkout->distance_km,
                'duration_seconds' => (int) $maxCaloriesWorkout->duration_seconds,
                'calories' => (int) $maxCaloriesWorkout->calories,
            ] : null,
        ];

        $strength = $strengthService->getExercisesSummaryForUser($user);

        return [
            'endurance' => $endurance,
            'strength' => $strength,
        ];
    }

    protected function buildEnduranceRecordsForType(Collection $allWorkouts, string $type): array
    {
        $workouts = $allWorkouts
            ->where('type', $type)
            ->filter(function (Workout $workoutModel) {
                return $workoutModel->distance_km > 0 && $workoutModel->duration_seconds > 0;
            });

        if ($workouts->isEmpty()) {
            return [
                'longest' => null,
                'fastest_overall' => null,
                'max_calories' => null,
            ];
        }

        $longestWorkout = $workouts
            ->sortByDesc('distance_km')
            ->first();

        $fastestWorkout = $workouts
            ->sortBy(function (Workout $workoutModel) {
                if ($workoutModel->distance_km <= 0) {
                    return PHP_FLOAT_MAX;
                }

                return $workoutModel->duration_seconds / max($workoutModel->distance_km, 0.0001);
            })
            ->first();

        $maxCaloriesWorkout = $workouts
            ->filter(function (Workout $workoutModel) {
                return $workoutModel->calories !== null && $workoutModel->calories > 0;
            })
            ->sortByDesc('calories')
            ->first();

        return [
            'longest' => $longestWorkout ? [
                'workout_id' => $longestWorkout->id,
                'date' => optional($longestWorkout->date)->toDateString(),
                'distance_km' => (float) $longestWorkout->distance_km,
                'duration_seconds' => (int) $longestWorkout->duration_seconds,
            ] : null,
            'fastest_overall' => $fastestWorkout ? [
                'workout_id' => $fastestWorkout->id,
                'date' => optional($fastestWorkout->date)->toDateString(),
                'distance_km' => (float) $fastestWorkout->distance_km,
                'duration_seconds' => (int) $fastestWorkout->duration_seconds,
                'pace_seconds_per_km' => (int) round(
                    $fastestWorkout->duration_seconds / max($fastestWorkout->distance_km, 0.0001)
                ),
            ] : null,
            'max_calories' => $maxCaloriesWorkout ? [
                'workout_id' => $maxCaloriesWorkout->id,
                'date' => optional($maxCaloriesWorkout->date)->toDateString(),
                'distance_km' => (float) $maxCaloriesWorkout->distance_km,
                'duration_seconds' => (int) $maxCaloriesWorkout->duration_seconds,
                'calories' => (int) $maxCaloriesWorkout->calories,
            ] : null,
        ];
    }
}
