<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutExerciseStatsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkoutAnalyticsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/workouts', [WorkoutController::class,'index'])->name('workouts.index');
    Route::get('/workouts/create', [WorkoutController::class,'create'])->name('workouts.create');
    Route::post('/workouts', [WorkoutController::class,'store'])->name('workouts.store');
    Route::get('/workouts/{workout}', [WorkoutController::class,'show'])->name('workouts.show');

    Route::get('/workouts/{workout}/edit', [WorkoutController::class,'edit'])->name('workouts.edit');
    Route::put('/workouts/{workout}', [WorkoutController::class,'update'])->name('workouts.update');
    Route::delete('/workouts/{workout}', [WorkoutController::class,'destroy'])->name('workouts.destroy');

    Route::get('/analytics', [WorkoutAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/records', [WorkoutAnalyticsController::class, 'records'])->name('records.index');

    Route::get('/workouts/exercises/{workoutExercise}/stats', [WorkoutExerciseStatsController::class,'show'])->name('workouts.exercise.stats');
    Route::get('/strength-exercises', [WorkoutExerciseStatsController::class, 'index'])->name('strength.exercises.index');
});

require __DIR__.'/auth.php';
