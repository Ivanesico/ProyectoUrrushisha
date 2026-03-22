<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller {

    public function register(RegisterRequest $request) {
        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();
        return redirect()
                        ->route('home')
                        ->with('success', 'Usuario registrado con éxito');
    }

    public function login(LoginRequest $request) {
        $usuario = User::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return back()
                            ->withErrors(['email' => 'Credenciales incorrectas'])
                            ->withInput();
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function me() {
        $usuario = Auth::user();

        if (!$usuario) {
            return response()->json([
                        'success' => false,
                        'message' => 'Usuario no autenticado'
                            ], 401);
        }

        return response()->json([
                    'success' => true,
                    'user' => [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'email' => $usuario->email,
                        'is_admin' => $usuario->is_admin,
                    ]
                        ], 200);
    }
}
