<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $durationSeconds = (int) ($this->resource->duration_seconds ?? 0);

        return [
            'type' => 'WorkoutDetail',
            'id' => $this->resource->id,
            'attributes' => [
                'date' => $this->formatDate($this->resource->date),
                'type' => $this->resource->type,
                'duration_seconds' => $durationSeconds,
                'duration' => $this->formatDuration($durationSeconds),
                'distance_km' => (float) $this->resource->distance_km,
                'calories' => (int) $this->resource->calories,
                'notes' => $this->resource->notes,
                'created_at' => $this->formatDateTime($this->resource->created_at),
                'updated_at' => $this->formatDateTime($this->resource->updated_at),
            ],
            'relationships' => [
                'user' => $this->resource->relationLoaded('user') && $this->resource->user ? [
                    'id' => $this->resource->user->id,
                    'name' => $this->resource->user->name,
                    'email' => $this->resource->user->email,
                ] : null,
                'exercises' => $this->buildExercisesRelationship(),
            ],
            'links' => [
                'self' => route('workouts.show', ['workout' => $this->resource->id]),
                'edit' => route('workouts.edit', ['workout' => $this->resource->id]),
            ],
        ];
    }

    protected function buildExercisesRelationship(): array
    {
        if ($this->resource->type !== 'Strength') {
            return [];
        }

        $exercises = $this->resource->relationLoaded('workoutExercises')
            ? $this->resource->workoutExercises
            : collect();

        return $exercises
            ->map(function ($exerciseModel) {
                $sets = $exerciseModel->relationLoaded('sets')
                    ? $exerciseModel->sets
                    : collect();

                return [
                    'type' => 'WorkoutExercise',
                    'id' => $exerciseModel->id,
                    'attributes' => [
                        'exercise_id' => $exerciseModel->exercise_id,
                        'external_source' => $exerciseModel->external_source,
                        'external_id' => $exerciseModel->external_id,
                        'name' => $exerciseModel->name,
                        'order_no' => $exerciseModel->order_no,
                    ],
                    'relationships' => [
                        'sets' => $sets
                            ->map(function ($setModel) {
                                return [
                                    'type' => 'WorkoutSet',
                                    'id' => $setModel->id,
                                    'attributes' => [
                                        'set_no' => $setModel->set_no,
                                        'reps' => $setModel->reps,
                                        'weight_kg' => $setModel->weight_kg,
                                        'rir' => $setModel->rir,
                                        'rest_seconds' => $setModel->rest_seconds,
                                    ],
                                ];
                            })
                            ->values()
                            ->all(),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '00:00:00';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    protected function formatDate($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    protected function formatDateTime($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i');
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
    }
}
