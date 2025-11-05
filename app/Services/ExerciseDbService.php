<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class ExerciseDbService
{
    public function search(string $query, int $limit = null): array
    {
        $baseUrl = rtrim((string) Config::get('exercisedb.base_url'), '/');
        $timeoutSeconds = (int) Config::get('exercisedb.timeout');
        $take = $limit ?: (int) Config::get('exercisedb.limit');

        $http = Http::timeout($timeoutSeconds);
        $apiKeyHeader = (string) Config::get('exercisedb.api_key_header');
        $apiKey = (string) Config::get('exercisedb.api_key');
        if ($apiKeyHeader !== '' && $apiKey !== '') {
            $http = $http->withHeaders([$apiKeyHeader => $apiKey]);
        }
        $hostHeader = (string) Config::get('exercisedb.host');
        if ($hostHeader !== '') {
            $http = $http->withHeaders(['X-RapidAPI-Host' => $hostHeader]);
        }

        $results = $this->tryV2($http, $baseUrl, $query, $take);
        if (empty($results)) {
            $results = $this->tryV1Name($http, $baseUrl, $query, $take);
        }
        if (empty($results)) {
            $results = $this->tryV1FallbackList($http, $baseUrl, $take);
        }

        return $results;
    }

    private function tryV2($http, string $baseUrl, string $query, int $take): array
    {
        $response = $http->get($baseUrl . '/v2/exercises/search', [
            'query' => $query,
            'limit' => $take,
        ]);
        if (!$response->ok()) {
            return [];
        }
        $items = $response->json();
        return $this->normalizeMany(is_array($items) ? $items : [], $take);
    }

    private function tryV1Name($http, string $baseUrl, string $query, int $take): array
    {
        $response = $http->get($baseUrl . '/exercises/name/' . rawurlencode($query));
        if (!$response->ok()) {
            return [];
        }
        $items = $response->json();
        return $this->normalizeMany(is_array($items) ? $items : [], $take);
    }

    private function tryV1FallbackList($http, string $baseUrl, int $take): array
    {
        $response = $http->get($baseUrl . '/exercises');
        if (!$response->ok()) {
            return [];
        }
        $items = $response->json();
        return $this->normalizeMany(is_array($items) ? array_slice($items, 0, $take) : [], $take);
    }

    private function normalizeMany(array $items, int $take): array
    {
        $normalized = array_values(array_filter(array_map(function (array $row) {
            $id = (string) ($row['exerciseId'] ?? $row['id'] ?? $row['_id'] ?? '');
            $name = (string) ($row['name'] ?? ($row['title'] ?? ''));
            if ($name === '' && $id !== '') {
                $name = 'Exercise #' . $id;
            }

            $bodyParts = [];
            if (isset($row['bodyParts']) && is_array($row['bodyParts'])) {
                $bodyParts = array_values(array_filter(array_map('strval', $row['bodyParts'])));
            } elseif (isset($row['bodyPart']) && is_string($row['bodyPart'])) {
                $bodyParts = [$row['bodyPart']];
            }

            $equipments = [];
            if (isset($row['equipments']) && is_array($row['equipments'])) {
                $equipments = array_values(array_filter(array_map('strval', $row['equipments'])));
            } elseif (isset($row['equipment']) && is_string($row['equipment'])) {
                $equipments = [$row['equipment']];
            }

            return [
                'source' => 'exercisedb',
                'id' => $id,
                'name' => $name,
                'muscle_group' => $bodyParts[0] ?? null,
                'external_source' => 'exercisedb',
                'external_id' => $id,
                'body_parts' => $bodyParts,
                'equipments' => $equipments,
                'image_url' => $row['imageUrl'] ?? $row['gifUrl'] ?? null,
                'video_url' => $row['videoUrl'] ?? null,
            ];
        }, $items), fn ($r) => !empty($r['name'])));

        return array_slice($normalized, 0, $take);
    }
}
