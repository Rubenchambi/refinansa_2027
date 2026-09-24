<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Intentamos autenticar
        if (!Auth::attempt($request->only('email', 'password'))) {
            // Lanzamos una excepción de validación para que maneje el error limpiamente
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas o la cuenta no existe.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Opcional: Validar si el usuario está activo (por si lo dan de baja en la empresa)
        if (isset($user->estado) && $user->estado !== 'Activo') {
            return response()->json([
                'message' => 'Tu cuenta se encuentra inactiva. Comunícate con el administrador.'
            ], 403);
        }

        // Creamos el token de acceso con Sanctum
        // Opcional: Puedes revocar tokens anteriores si quieres sesión única por usuario
        $user->tokens()->delete(); 
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => '¡Bienvenido a CobranzaOS!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'asesor'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Revoca el token actual con el que se hizo la petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ]);
    }
}