<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Muestra la pantalla de Inicio de Sesión / Registro de DataMind Studio.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('hub.index');
        }

        return view('auth.login');
    }

    /**
     * Autentica al usuario con correo y contraseña.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember', true);

        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('hub.index'));
        }

        // Verificar si existe el usuario pero se encuentra inactivo
        $inactiveUser = User::where('email', $credentials['email'])->where('is_active', false)->first();
        if ($inactiveUser && Hash::check($credentials['password'], $inactiveUser->password)) {
            return back()->withErrors([
                'email' => 'Esta cuenta de usuario ha sido inactivada por un administrador.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Registra una nueva cuenta de usuario e inicia sesión automáticamente.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('hub.index');
    }

    /**
     * Cierra la sesión activa del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
