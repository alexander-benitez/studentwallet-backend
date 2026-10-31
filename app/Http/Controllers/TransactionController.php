<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('category')
            ->where('user_id', Auth::id());

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        $transactions = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data'   => TransactionResource::collection($transactions),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount'      => 'required|numeric|gt:0',
            'type'        => 'required|in:ingreso,gasto',
            'description' => 'nullable|string|max:255',
            'date'        => 'required|date',
        ]);

        $category = Category::find($validated['category_id']);

        if (!$category->is_default && $category->user_id !== null && $category->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'La categoría seleccionada no pertenece ni está disponible para el usuario autenticado',
            ], 403);
        }

        if ($category->type !== $validated['type']) {
            return response()->json([
                'status'  => 'error',
                'message' => "El tipo del movimiento ({$validated['type']}) no coincide con el tipo de la categoría seleccionada ({$category->type})",
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id'     => Auth::id(),
            'category_id' => $validated['category_id'],
            'amount'      => $validated['amount'],
            'type'        => $validated['type'],
            'description' => $validated['description'] ?? null,
            'date'        => $validated['date'],
        ]);

        $transaction->load('category');

        return response()->json([
            'status'  => 'success',
            'message' => 'Movimiento registrado exitosamente',
            'data'    => new TransactionResource($transaction),
        ], 201);
    }

    public function show(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado para consultar este movimiento financiero',
            ], 403);
        }

        $transaction->load('category');

        return response()->json([
            'status' => 'success',
            'data'   => new TransactionResource($transaction),
        ], 200);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado para modificar este movimiento financiero',
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount'      => 'sometimes|required|numeric|gt:0',
            'type'        => 'sometimes|required|in:ingreso,gasto',
            'description' => 'nullable|string|max:255',
            'date'        => 'sometimes|required|date',
        ]);

        $targetCategoryId = $validated['category_id'] ?? $transaction->category_id;
        $targetType = $validated['type'] ?? $transaction->type;

        $category = Category::find($targetCategoryId);

        if (!$category->is_default && $category->user_id !== null && $category->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'La categoría seleccionada no pertenece ni está disponible para el usuario autenticado',
            ], 403);
        }

        if ($category->type !== $targetType) {
            return response()->json([
                'status'  => 'error',
                'message' => "El tipo del movimiento ({$targetType}) no coincide con el tipo de la categoría ({$category->type})",
            ], 422);
        }

        $transaction->update($validated);
        $transaction->load('category');

        return response()->json([
            'status'  => 'success',
            'message' => 'Movimiento actualizado exitosamente',
            'data'    => new TransactionResource($transaction),
        ], 200);
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado para eliminar este movimiento financiero',
            ], 403);
        }

        $transaction->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Movimiento eliminado exitosamente',
        ], 200);
    }
}
