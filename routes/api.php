<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LogoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API для работы с изображениями
Route::prefix('images')->group(function () {
    Route::post('/upload', [ImageController::class, 'upload']);
    Route::get('/', [ImageController::class, 'index']);
    Route::delete('/{id}', [ImageController::class, 'destroy']);
    Route::post('/reorder', [ImageController::class, 'reorder']);
    Route::patch('/{id}/toggle-active', [ImageController::class, 'toggleActive']);
});

// API для работы с логотипами
Route::prefix('logos')->group(function () {
    Route::post('/upload', [LogoController::class, 'upload']);
    Route::delete('/', [LogoController::class, 'delete']);
});
