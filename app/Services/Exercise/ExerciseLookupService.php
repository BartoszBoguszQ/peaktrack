<?php

namespace App\Services\Exercise;

use App\Models\Exercise;
use App\Services\ExerciseDbService;
use Illuminate\Support\Collection;

class ExerciseLookupService
{
    protected ExerciseDbService $exerciseDbService;

    public function __construct(ExerciseDbService $exerciseDbService)
    {
        $this->exerciseDbService = $exerciseDbService;
    }

    public function search(string $queryString, int $limit = 10): array
    {
        $trimmedQuery = trim($queryString);
        if ($trimmedQuery === '') {
            return [];
        }

        $normalizedLimit = $limit > 0 ? min($limit, 50) : 10;

        $localResults = $this->searchLocal($trimmedQuery, $normalizedLimit);
        $externalResults = $this->searchExternal($trimmedQuery, $normalizedLimit);

        return $this->mergeResults($localResults, $externalResults);
    }

    protected function searchLocal(string $queryString, int $limit): array
    {
        return Exercise::query()
            ->where('name', 'like', '%' . $queryString . '%')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Exercise $exerciseModel) {
                return [
                    'source' => 'local',
                    'id' => (string) $exerciseModel->id,
                    'name' => $exerciseModel->name,
                    'muscle_group' => $exerciseModel->muscle_group,
                    'external_source' => null,
                    'external_id' => null,
                    'body_parts' => [],
                    'equipments' => [],
                    'image_url' => null,
                    'video_url' => null,
                ];
            })
            ->all();
    }

    protected function searchExternal(string $queryString, int $limit): array
    {
        $results = $this->exerciseDbService->search($queryString, $limit);

        if ($results instanceof Collection) {
            return $results->map(function (array $row) {
                return $this->normalizeExternalRow($row);
            })->all();
        }

        if (is_array($results)) {
            return collect($results)
                ->map(function (array $row) {
                    return $this->normalizeExternalRow($row);
                })
                ->all();
        }

        return [];
    }

    protected function normalizeExternalRow(array $row): array
    {
        return [
            'source' => $row['source'] ?? 'external',
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? '',
            'muscle_group' => $row['muscle_group'] ?? null,
            'external_source' => $row['external_source'] ?? ($row['source'] ?? 'external'),
            'external_id' => $row['external_id'] ?? ($row['id'] ?? null),
            'body_parts' => $row['body_parts'] ?? [],
            'equipments' => $row['equipments'] ?? [],
            'image_url' => $row['image_url'] ?? null,
            'video_url' => $row['video_url'] ?? null,
        ];
    }

    protected function mergeResults(array $localResults, array $externalResults): array
    {
        return collect($localResults)
            ->merge($externalResults)
            ->unique(function (array $row) {
                $nameKey = mb_strtolower((string) ($row['name'] ?? ''));
                $sourceKey = (string) ($row['source'] ?? '');
                $externalIdKey = (string) ($row['external_id'] ?? '');

                return $nameKey . '|' . $sourceKey . '|' . $externalIdKey;
            })
            ->values()
            ->all();
    }
}
