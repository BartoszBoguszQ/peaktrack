<?php

namespace App\Services\Exercise;

use App\Models\Exercise;
use App\Models\User;
use App\Services\ExerciseDbService;
use Illuminate\Support\Collection;

class ExerciseLookupService
{
    protected ExerciseDbService $exerciseDbService;

    public function __construct(ExerciseDbService $exerciseDbService)
    {
        $this->exerciseDbService = $exerciseDbService;
    }

    public function search(User $user, string $queryString, int $limit = 10): array
    {
        $trimmedQuery = trim($queryString);
        if ($trimmedQuery === '') {
            return [];
        }

        $normalizedLimit = $limit > 0 ? min($limit, 50) : 10;

        $localResults = $this->searchLocal($user, $trimmedQuery, $normalizedLimit);
        $externalResults = $this->searchExternal($trimmedQuery, $normalizedLimit);

        return $this->mergeResults($localResults, $externalResults);
    }

    protected function searchLocal(User $user, string $queryString, int $limit): array
    {
        return Exercise::query()
            ->where('user_id', $user->id)
            ->where('source', 'manual')
            ->where('name', 'like', '%' . $queryString . '%')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(function (Exercise $exerciseModel) {
                return [
                    'lookup_source' => 'local',
                    'id' => (string) $exerciseModel->id,
                    'name' => $exerciseModel->name,
                    'source' => 'manual',
                    'external_id' => null,
                ];
            })
            ->all();
    }

    protected function searchExternal(string $queryString, int $limit): array
    {
        $results = $this->exerciseDbService->search($queryString, $limit);

        if ($results instanceof Collection) {
            return $results->map(fn (array $row) => $this->normalizeExternalRow($row))->all();
        }

        if (is_array($results)) {
            return collect($results)->map(fn (array $row) => $this->normalizeExternalRow($row))->all();
        }

        return [];
    }

    protected function normalizeExternalRow(array $row): array
    {
        $externalId = $row['external_id'] ?? ($row['id'] ?? null);

        return [
            'lookup_source' => 'api',
            'id' => $row['id'] ?? null,
            'name' => $row['name'] ?? '',
            'source' => 'api',
            'external_id' => $externalId !== null ? (string) $externalId : null,
        ];
    }

    protected function mergeResults(array $localResults, array $externalResults): array
    {
        return collect($localResults)
            ->merge($externalResults)
            ->unique(function (array $row) {
                $nameKey = mb_strtolower((string) ($row['name'] ?? ''));
                $lookupKey = (string) ($row['lookup_source'] ?? '');
                $externalIdKey = (string) ($row['external_id'] ?? '');

                return $nameKey . '|' . $lookupKey . '|' . $externalIdKey;
            })
            ->values()
            ->all();
    }
}
