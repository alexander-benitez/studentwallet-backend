<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // 1. ENDPOINT DE REGISTRO
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    // 2. ENDPOINT DE LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'plataforma' => 'nullable|in:web,movil',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        $user = Auth::user();

        // VALIDACIÓN: Si el usuario es administrador, no puede iniciar sesión desde móvil
        if ($request->input('plataforma') === 'movil' && $user->rol === 'administrador') {
            Auth::guard('web')->logout();

            return response()->json([
                'status' => 'error',
                'message' => 'Acceso restringido: solo estudiantes pueden usar la app móvil.',
            ], 403);
        }

        // VALIDACIÓN: Si el usuario está desactivado, no puede iniciar sesión
        if ($user->is_active === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login exitoso',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ], 200);
    }

    // 3. ENDPOINT DE LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente',
        ], 200);
    }

    // 4. ENDPOINT PARA ACTUALIZAR PERFIL
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'apellido' => 'sometimes|nullable|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'universidad' => 'sometimes|nullable|string|max:255',
            'carrera' => 'sometimes|nullable|string|max:255',
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
        ]);

        foreach (['name', 'apellido', 'email', 'universidad', 'carrera'] as $field) {
            if (array_key_exists($field, $validated)) {
                $user->{$field} = $validated[$field];
            }
        }

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil actualizado correctamente',
            'data' => new UserResource($user),
        ], 200);
    }
}
