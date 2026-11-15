<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = Budget::where('user_id', Auth::id())
            ->with('category')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        if ($request->filled('month')) {
            $query->where('month', $request->integer('month'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->integer('year'));
        }

        $budgets = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $budgets,
        ], 200);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|digits:4',
        ]);

        $category = Category::find($validated['category_id']);

        if ($category && ! $category->is_default && $category->user_id !== null && $category->user_id !== $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'La categoria seleccionada no pertenece ni esta disponible para el usuario autenticado',
            ], 403);
        }

        if ($category && $category->type !== 'gasto') {
            return response()->json([
                'status' => 'error',
                'message' => 'El tipo del presupuesto debe corresponder a una categoria de gasto',
            ], 422);
        }

        $budget = Budget::where('user_id', $userId)
            ->where('category_id', $validated['category_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->first();

        if ($budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ya existe un presupuesto para esta categoría en ese mes y año.',
            ], 422);
        }

        try {
            $budget = Budget::create([
                'user_id' => $userId,
                'category_id' => $validated['category_id'],
                'amount' => $validated['amount'],
                'month' => $validated['month'],
                'year' => $validated['year'],
            ]);
        } catch (QueryException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ya existe un presupuesto para esta categoría en ese mes y año.',
            ], 422);
        }

        $budget->load('category');

        return response()->json([
            'status' => 'success',
            'data' => $budget,
        ], 201);
    }

    public function show($id)
    {
        $budget = Budget::where('user_id', Auth::id())
            ->with('category')
            ->find($id);

        if (! $budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'Presupuesto no encontrado',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $budget,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $budget = Budget::where('user_id', Auth::id())->find($id);

        if (! $budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'Presupuesto no encontrado',
            ], 404);
        }

        $userId = Auth::id();

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'month' => 'sometimes|required|integer|between:1,12',
            'year' => 'sometimes|required|integer|digits:4',
        ]);

        if ($request->hasAny(['category_id', 'month', 'year'])) {
            $duplicate = Budget::where('user_id', $userId)
                ->where('category_id', $validated['category_id'] ?? $budget->category_id)
                ->where('month', $validated['month'] ?? $budget->month)
                ->where('year', $validated['year'] ?? $budget->year)
                ->where('id', '!=', $budget->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ya existe un presupuesto para esta categoría en ese mes y año.',
                ], 422);
            }
        }

        $budget->update($validated);
        $budget->load('category');

        return response()->json([
            'status' => 'success',
            'data' => $budget,
        ], 200);
    }

    public function destroy($id)
    {
        $budget = Budget::where('user_id', Auth::id())->find($id);

        if (! $budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'Presupuesto no encontrado',
            ], 404);
        }

        $budget->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Presupuesto eliminado',
        ], 200);
    }
}
