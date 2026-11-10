<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::where('user_id', Auth::id())
            ->with('category')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $budgets,
        ], 200);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                Rule::unique('budgets')->where(function ($query) use ($userId, $request) {
                    return $query->where('user_id', $userId)
                                 ->where('category_id', $request->input('category_id'))
                                 ->where('month', $request->input('month'))
                                 ->where('year', $request->input('year'));
                }),
            ],
            'amount' => 'required|numeric|min:0.01',
            'month'  => 'required|integer|between:1,12',
            'year'   => 'required|integer|digits:4',
        ], [
            'category_id.unique' => 'Ya existe un presupuesto para esta categoría en ese mes y año.',
        ]);

        $budget = Budget::create([
            'user_id'     => $userId,
            'category_id' => $validated['category_id'],
            'amount'      => $validated['amount'],
            'month'       => $validated['month'],
            'year'        => $validated['year'],
        ]);

        $budget->load('category');

        return response()->json([
            'status' => 'success',
            'data'   => $budget,
        ], 201);
    }

    public function show($id)
    {
        $budget = Budget::where('user_id', Auth::id())
            ->with('category')
            ->find($id);

        if (! $budget) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Presupuesto no encontrado',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $budget,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $budget = Budget::where('user_id', Auth::id())->find($id);

        if (! $budget) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Presupuesto no encontrado',
            ], 404);
        }

        $userId = Auth::id();

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount'      => 'sometimes|required|numeric|min:0.01',
            'month'       => 'sometimes|required|integer|between:1,12',
            'year'        => 'sometimes|required|integer|digits:4',
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
                    'status'  => 'error',
                    'message' => 'Ya existe un presupuesto para esta categoría en ese mes y año.',
                ], 422);
            }
        }

        $budget->update($validated);
        $budget->load('category');

        return response()->json([
            'status' => 'success',
            'data'   => $budget,
        ], 200);
    }

    public function destroy($id)
    {
        $budget = Budget::where('user_id', Auth::id())->find($id);

        if (! $budget) {
            return response()->json([
                'status'  => 'error',
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
