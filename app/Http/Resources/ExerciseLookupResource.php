<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseLookupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'type' => 'ExerciseLookupResult',
            'id' => $this['id'] ?? null,
            'attributes' => [
                'name' => $this['name'] ?? '',
                'muscle_group' => $this['muscle_group'] ?? null,
                'body_parts' => $this['body_parts'] ?? [],
                'equipments' => $this['equipments'] ?? [],
                'image_url' => $this['image_url'] ?? null,
                'video_url' => $this['video_url'] ?? null,
            ],
            'relationships' => [
                'source' => [
                    'source' => $this['source'] ?? null,
                    'external_source' => $this['external_source'] ?? null,
                    'external_id' => $this['external_id'] ?? null,
                ],
            ],
            'links' => [],
        ];
    }
}
