<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    /**
     * Calcula el balance financiero del usuario autenticado.
     * Soporta filtros opcionales de fechas (start_date y end_date).
     */
    public function getBalance(Request $request)
    {
        $userId = Auth::id();

        $query = Transaction::where('user_id', $userId);

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->query('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->query('end_date'));
        }

        // Agregación de ingresos y gastos
        $totalIncomes = (float) (clone $query)->where('type', 'ingreso')->sum('amount');
        $totalExpenses = (float) (clone $query)->where('type', 'gasto')->sum('amount');
        $balance = $totalIncomes - $totalExpenses;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_incomes'  => round($totalIncomes, 2),
                'total_expenses' => round($totalExpenses, 2),
                'balance'        => round($balance, 2),
                'is_negative'    => $balance < 0,
                'currency'       => 'USD',
                'period'         => [
                    'start_date' => $request->query('start_date', null),
                    'end_date'   => $request->query('end_date', null),
                ],
            ],
        ], 200);
    }
}
