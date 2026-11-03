<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Suggestion;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users()
    {
        $users = User::select("id", "name", "email", "rol", "is_active", "created_at")
            ->orderBy("id", "desc")
            ->get();

        return response()->json([
            "status" => "success",
            "data" => $users
        ], 200);
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                "status" => "error",
                "message" => "Usuario no encontrado"
            ], 404);
        }

        $request->validate([
            "is_active" => "required|boolean"
        ]);

        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            "status" => "success",
            "message" => "Estado del usuario actualizado exitosamente",
            "data" => [
                "id" => $user->id,
                "name" => $user->name,
                "is_active" => $user->is_active
            ]
        ], 200);
    }

    public function suggestions()
    {
        $suggestions = Suggestion::with("user:id,name,email")
            ->orderBy("created_at", "desc")
            ->get();

        return response()->json([
            "status" => "success",
            "data" => $suggestions
        ], 200);
    }

    public function storeSuggestion(Request $request)
    {
        $request->validate([
            "message" => "required|string|max:1000",
            "title" => "nullable|string|max:150"
        ]);

        $suggestion = Suggestion::create([
            "user_id" => $request->user()->id,
            "title" => $request->title,
            "message" => $request->message,
            "status" => "pendiente"
        ]);

        return response()->json([
            "status" => "success",
            "message" => "Sugerencia enviada correctamente",
            "data" => $suggestion
        ], 201);
    }
}
