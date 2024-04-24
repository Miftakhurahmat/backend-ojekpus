<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\OrderController;

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

Route::get('/', function () {
    return response()->json(['API Ready'], 200);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:api'])->group(function () {
    Route::post('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/history', [HistoryController::class, 'index']);
    Route::get('/waiting', [HistoryController::class, 'waiting']);
    Route::get('/process', [HistoryController::class, 'process']);
    Route::get('/process-list', [HistoryController::class, 'processDriver']);
    Route::get('/driver/history', [HistoryController::class, 'historyDriver']);
    
    Route::get('/order', [OrderController::class, 'index']);
    Route::post('/order', [OrderController::class, 'store']);
    Route::get('/order/{order}', [OrderController::class, 'show']);
    Route::put('/order/{order}', [OrderController::class, 'update']);
    Route::post('/order/{order}', [OrderController::class, 'done']);
    
    Route::get('/drivers', [OrderController::class, 'listDriver']);
    Route::put('/drivers/{id}/update',  [OrderController::class, 'updateLastOrderTakenAt']);
});
