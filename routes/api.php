<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\TransactionController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Rutas protegidas (Requieren Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return response()->json([
            'status' => 'success',
            'data' => new UserResource($request->user()),
        ], 200);
    });

    Route::put('/user', [AuthController::class, 'updateProfile']);

    Route::get('/user/backup', [BackupController::class, 'backup']);

    // Endpoints REST de Categorías
    Route::apiResource('categories', CategoryController::class);

    // Endpoints REST de Movimientos Financieros
    Route::apiResource('transactions', TransactionController::class);

    // Endpoint de Cálculo de Saldo Financiero
    Route::get('/balance', [BalanceController::class, 'getBalance']);

    // Endpoints REST de Metas y Aportes
    Route::apiResource('goals', GoalController::class);
    Route::post('/goals/{goal}/contributions', [GoalController::class, 'addContribution']);

    // Endpoints REST de Presupuestos Mensuales
    Route::apiResource('budgets', BudgetController::class);

    // Endpoint de Recomendaciones Personalizadas
    Route::get('/recommendations', [RecommendationController::class, 'index']);
});

use Illuminate\Support\Facades\Route;

// Rutas autenticadas de estudiantes y administración
Route::middleware('auth:sanctum')->group(function () {
    // Envío de sugerencias por estudiantes
    Route::post('/suggestions', [AdminController::class, 'storeSuggestion']);
    Route::get('/suggestions', [AdminController::class, 'mySuggestions']);

    // Panel Administrativo protegido
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{id}/status', [AdminController::class, 'toggleUserStatus']);
        Route::put('/suggestions/{id}', [AdminController::class, 'updateSuggestionStatus']);
        Route::get('/suggestions', [AdminController::class, 'suggestions']);
        Route::get('/reports', [AdminController::class, 'reports']);
    });
});
