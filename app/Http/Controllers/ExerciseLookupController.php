<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExerciseSearchRequest;
use App\Http\Resources\ExerciseLookupResource;
use App\Services\Exercise\ExerciseLookupService;

class ExerciseLookupController extends Controller
{
    public function search(ExerciseSearchRequest $request, ExerciseLookupService $exerciseLookupService)
    {
        $queryString = (string) $request->input('query', '');
        $limit = (int) $request->input('limit', 10);

        $results = $exerciseLookupService->search($queryString, $limit);

        return ExerciseLookupResource::collection(collect($results));
    }
}
