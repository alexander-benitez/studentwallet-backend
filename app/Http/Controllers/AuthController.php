<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // 1. ENDPOINT DE REGISTRO
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|ends_with:@gmail.com|unique:users,email',
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

    // 5. SOLICITUD DE RECUPERACIÓN DE CONTRASEÑA
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->reset_code = $code;
        $user->reset_code_expires_at = now()->addMinutes(15);
        $user->save();

        Mail::raw(
            "Tu código de recuperación es: {$code}. Expira en 15 minutos.",
            function ($message) use ($user) {
                $message->to($user->email)->subject('Código de recuperación de contraseña');
            }
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Código de recuperación enviado al correo',
        ], 200);
    }

    // 6. RESETEO DE CONTRASEÑA
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user->reset_code || $user->reset_code !== $validated['code'] || ! $user->reset_code_expires_at || now()->greaterThan($user->reset_code_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Código inválido o expirado',
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->reset_code = null;
        $user->reset_code_expires_at = null;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente',
        ], 200);
    }
}
