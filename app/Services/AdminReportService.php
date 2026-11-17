<?php

namespace App\Services;

use App\Models\Transaction;

class AdminReportService
{
    public function getReportData(array $filters): array
    {
        $startDate = $filters['start_date'] ?? '1900-01-01';
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $transactions = Transaction::with(['user:id,name,email', 'category:id,name,type,color,icon'])
            ->whereBetween('date', [
                $startDate,
                $endDate,
            ])
            ->get();

        $totalIncomes = (float) $transactions
            ->where('type', 'ingreso')
            ->sum('amount');

        $totalExpenses = (float) $transactions
            ->where('type', 'gasto')
            ->sum('amount');

        $totalBalance = $totalIncomes - $totalExpenses;

        $monthlyTotals = [];

        foreach ($transactions as $transaction) {
            $monthKey = $transaction->date->format('Y-m');
            $monthlyTotals[$monthKey] ??= [
                'month' => $monthKey,
                'ingresos' => 0.0,
                'gastos' => 0.0,
            ];

            if ($transaction->type === 'ingreso') {
                $monthlyTotals[$monthKey]['ingresos'] += (float) $transaction->amount;
            }

            if ($transaction->type === 'gasto') {
                $monthlyTotals[$monthKey]['gastos'] += (float) $transaction->amount;
            }
        }

        foreach ($monthlyTotals as &$monthlyTotal) {
            $monthlyTotal['balance'] = $monthlyTotal['ingresos'] - $monthlyTotal['gastos'];
        }
        unset($monthlyTotal);

        $categoryTotals = [];

        foreach ($transactions as $transaction) {
            if ($transaction->type !== 'gasto') {
                continue;
            }

            $categoryId = $transaction->category?->id;
            $categoryName = $transaction->category?->name ?? 'Sin categoría';
            $categoryKey = (string) ($categoryId ?? $categoryName);

            if (! isset($categoryTotals[$categoryKey])) {
                $categoryTotals[$categoryKey] = [
                    'name' => $categoryName,
                    'amount' => 0.0,
                    'color' => $transaction->category?->color ?? '#64748B',
                    'icon' => $transaction->category?->icon ?? 'cash-outline',
                ];
            }

            $categoryTotals[$categoryKey]['amount'] += (float) $transaction->amount;
        }

        $categories = array_values(array_map(function (array $category) {
            return [
                'name' => $category['name'],
                'amount' => round($category['amount'], 2),
                'color' => $category['color'],
                'icon' => $category['icon'],
            ];
        }, $categoryTotals));

        return [
            'totals' => [
                'total_incomes' => round($totalIncomes, 2),
                'total_expenses' => round($totalExpenses, 2),
                'balance' => round($totalBalance, 2),
            ],
            'monthly' => array_values($monthlyTotals),
            'categories' => $categories,
        ];
    }
}
