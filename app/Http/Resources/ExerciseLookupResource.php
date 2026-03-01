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
                'lookup_source' => $this['lookup_source'] ?? null,
                'source' => $this['source'] ?? null,
                'external_id' => $this['external_id'] ?? null,
            ],
        ];
    }
}
