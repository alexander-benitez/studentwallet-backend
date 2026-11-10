<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\RecommendationController;
use App\Http\Resources\UserResource;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (Requieren Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data'   => new UserResource($request->user()),
        ], 200);
    });

    // Endpoints REST de Categorías
    Route::apiResource('categories', CategoryController::class);

    // Endpoints REST de Movimientos Financieros
    Route::apiResource('transactions', TransactionController::class);

    // Endpoint de Cálculo de Saldo Financiero
    Route::get('/balance', [BalanceController::class, 'getBalance']);

    // Endpoints REST de Metas y Aportes
    Route::apiResource('goals', GoalController::class);
    Route::post('/goals/{goal}/contributions', [GoalController::class, 'addContribution']);

    // Endpoint de Recomendaciones Personalizadas
    Route::get('/recommendations', [RecommendationController::class, 'index']);
});

use App\Http\Controllers\AdminController;

// Rutas autenticadas de estudiantes y administración
Route::middleware('auth:sanctum')->group(function () {
    // Envío de sugerencias por estudiantes
    Route::post('/suggestions', [AdminController::class, 'storeSuggestion']);
    Route::get('/suggestions', [AdminController::class, 'mySuggestions']);

    // Panel Administrativo protegido
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/status', [AdminController::class, 'toggleUserStatus']);
        Route::get('/suggestions', [AdminController::class, 'suggestions']);
    });
});
