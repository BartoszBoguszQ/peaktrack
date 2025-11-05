<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExerciseLookupController;

Route::get('/exercises/search', [ExerciseLookupController::class, 'search'])->name('api.exercises.search');
