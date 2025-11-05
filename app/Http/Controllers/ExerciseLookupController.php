<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Services\ExerciseDbService;
use Illuminate\Http\Request;

class ExerciseLookupController extends Controller
{
    public function search(Request $request, ExerciseDbService $exerciseDbService)
    {
        $queryString = trim((string) $request->string('query', ''));
        if ($queryString === '') {
            return response()->json(['data' => []]);
        }

        $localResults = Exercise::query()
            ->where('name', 'like', '%' . $queryString . '%')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function (Exercise $exerciseModel) {
                return [
                    'source' => 'local',
                    'id' => (string) $exerciseModel->id,
                    'name' => $exerciseModel->name,
                    'muscle_group' => $exerciseModel->muscle_group,
                    'external_source' => null,
                    'external_id' => null,
                ];
            })
            ->all();

        $externalResults = $exerciseDbService->search($queryString, 10);

        $mergedResults = collect($localResults)
            ->merge($externalResults)
            ->unique(function (array $row) {
                return mb_strtolower($row['name']) . '|' . ($row['source'] ?? '');
            })
            ->values()
            ->all();

        return response()->json(['data' => $mergedResults]);
    }
}
