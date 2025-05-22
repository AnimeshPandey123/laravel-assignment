<?php

use App\Http\Controllers\GeminiController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Route::get('/user', [UserController::class, 'index']);
// Route::post('/user', [UserController::class, 'store']);

// Route::post('/analyze-resume', [GeminiController::class, 'analyze']);
Route::middleware('auth:sanctum')->post('/analyze-resume', [GeminiController::class, 'analyzeResumeAndJobApplication']);

Route::prefix('resumes')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ResumeController::class, 'store']);
    Route::get('/', [ResumeController::class, 'index']);
    Route::get('/{id}', [ResumeController::class, 'show']);
    Route::put('/{id}', [ResumeController::class, 'update']);
    Route::delete('/{id}', [ResumeController::class, 'destroy']);
});

Route::prefix('jobs')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [JobApplicationController::class, 'store']);
    Route::get('/', [JobApplicationController::class, 'index']);
    Route::get('/{id}', [JobApplicationController::class, 'show']);
    Route::put('/{id}', [JobApplicationController::class, 'update']);
    Route::delete('/{id}', [JobApplicationController::class, 'destroy']);
});