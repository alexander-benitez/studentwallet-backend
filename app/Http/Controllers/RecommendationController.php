<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $recommendations = [];

        // Transacciones del usuario de los últimos 30 días
        $transactions = Transaction::with('category')
            ->where('user_id', $userId)
            ->where('date', '>=', today()->subDays(30))
            ->get();

        // 1. Recomendación por metas activas
        $activeGoals = Goal::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->where('calculated_status', 'active');

        foreach ($activeGoals as $goal) {
            $recommendations[] = [
                'type'    => 'goal',
                'message' => "Sigue aportando a tu meta: {$goal->name}",
            ];
        }

        // 2. Recomendación por gastos altos (más de 3 gastos en la última semana)
        $lastWeekExpenses = $transactions
            ->where('type', 'gasto')
            ->filter(function ($transaction) {
                return $transaction->date->greaterThanOrEqualTo(today()->subDays(7));
            });

        if ($lastWeekExpenses->count() > 3) {
            $categoryCounts = $lastWeekExpenses
                ->countBy(function ($transaction) {
                    return optional($transaction->category)->name ?? 'General';
                });

            $mostUsedCategory = $categoryCounts->keys()->first();

            $recommendations[] = [
                'type'    => 'spending',
                'message' => "Estás gastando mucho últimamente. Intenta reducir gastos en {$mostUsedCategory}",
            ];
        }

        // 3. Sin datos suficientes
        if (empty($recommendations)) {
            $recommendations[] = [
                'type'    => 'info',
                'message' => 'Registra más transacciones para recibir consejos personalizados.',
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => $recommendations,
        ], 200);
    }
}
