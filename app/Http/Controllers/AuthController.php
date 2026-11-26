<?php

namespace App\Http\Controllers;

use App\Models\Role;
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

        $emailLower = strtolower(trim($credentials['email']));
        $remember = $request->boolean('remember', true);

        $user = User::whereRaw('LOWER(email) = ?', [$emailLower])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            if (!$user->is_active) {
                return back()->withErrors([
                    'email' => 'Esta cuenta de usuario ha sido inactivada por un administrador.',
                ])->onlyInput('email');
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();
            return redirect()->intended(route('hub.index'));
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

        $adminRole = Role::where('slug', 'super-admin')->first() ?? Role::first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $adminRole?->id,
            'is_active' => true,
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
