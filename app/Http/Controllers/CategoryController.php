<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Category::where(function ($q) use ($userId) {
            $q->whereNull('user_id')
              ->orWhere('user_id', $userId);
        });

        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data'   => CategoryResource::collection($categories),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:ingreso,gasto',
        ]);

        $category = Category::create([
            'name'       => $validated['name'],
            'type'       => $validated['type'],
            'user_id'    => Auth::id(),
            'is_default' => false,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Categoría creada exitosamente',
            'data'    => new CategoryResource($category),
        ], 201);
    }

    public function show(Category $category)
    {
        if ($category->user_id !== null && $category->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado para consultar esta categoría',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => new CategoryResource($category),
        ], 200);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->is_default || $category->user_id === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se permite modificar categorías globales del sistema',
            ], 403);
        }

        if ($category->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado para modificar esta categoría',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'type' => 'sometimes|required|in:ingreso,gasto',
        ]);

        $category->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Categoría actualizada exitosamente',
            'data'    => new CategoryResource($category),
        ], 200);
    }

    public function destroy(Category $category)
    {
        if ($category->is_default || $category->user_id === null) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se permite eliminar categorías globales del sistema',
            ], 403);
        }

        if ($category->user_id !== Auth::id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No autorizado para eliminar esta categoría',
            ], 403);
        }

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Categoría eliminada exitosamente',
        ], 200);
    }
}
