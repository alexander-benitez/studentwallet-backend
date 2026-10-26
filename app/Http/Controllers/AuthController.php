<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // 1. ENDPOINT DE REGISTRO
    public function register(Request $request)
    {
        // Validar datos obligatorios y correo único
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email', // Rechaza si ya existe
            'password' => 'required|string|min:6',
        ]);

        // Crear el usuario (encriptando la contraseña)
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // Generar token automáticamente al registrarse
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 201); // 201 Created
    }

    // 2. ENDPOINT DE LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            // Error 401 sin revelar qué campo falló exactamente
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas'
            ], 401); 
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login exitoso',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 200); // 200 OK
    }

    // 3. ENDPOINT DE LOGOUT (Cerrar Sesión)
    public function logout(Request $request)
    {
        // Revoca el token actual con el que se está autenticando
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ], 200); // 200 OK
    }
}