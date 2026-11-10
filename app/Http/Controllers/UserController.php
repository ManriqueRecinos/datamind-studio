<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\FastApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    protected FastApiClient $fastApiClient;

    public function __construct(FastApiClient $fastApiClient)
    {
        $this->fastApiClient = $fastApiClient;
    }

    /**
     * Muestra la lista de usuarios y roles para administración.
     */
    public function index(): View
    {
        $users = User::with('role')->get();
        $roles = Role::where('is_active', true)->get();
        $isOnline = $this->fastApiClient->checkHealth();

        return view('settings.users', [
            'users' => $users,
            'roles' => $roles,
            'isOnline' => $isOnline,
        ]);
    }

    /**
     * Registra un nuevo usuario con 1 rol asignado.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('settings.users.index')->with('success', "Usuario '{$user->name}' registrado con éxito.");
    }

    /**
     * Actualiza información básica de un usuario y su rol.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => "required|email|unique:users,email,{$user->id}",
            'role_id' => 'nullable|exists:roles,id',
            'is_active' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('settings.users.index')->with('success', "Datos de '{$user->name}' actualizados correctamente.");
    }

    /**
     * Cambia la contraseña de un usuario determinado.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('settings.users.index')->with('success', "Contraseña actualizada para el usuario '{$user->name}'.");
    }

    /**
     * Alterna el estado activo / inactivo de un usuario.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('settings.users.index')->with('error', 'No puedes inactivar tu propia cuenta en sesión.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'activada' : 'inactivada';
        return redirect()->route('settings.users.index')->with('success', "La cuenta de '{$user->name}' ha sido {$statusStr}.");
    }

    /**
     * Asigna un único rol a un usuario mediante botón/modal directo.
     */
    public function assignRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $user->update(['role_id' => $validated['role_id'] ?? null]);

        $roleName = $user->role ? $user->role->name : 'Sin rol';
        return redirect()->route('settings.users.index')->with('success', "Rol '{$roleName}' asignado a '{$user->name}'.");
    }

    /**
     * Elimina un usuario del sistema (excepto el usuario actual).
     */
    public function destroy(User $user): RedirectResponse
    {
        if (Auth::id() === $user->id) {
            return redirect()->route('settings.users.index')->with('error', 'No puedes eliminar tu propia cuenta en sesión.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('settings.users.index')->with('success', "Usuario '{$name}' eliminado del sistema.");
    }

    /**
     * Retorna el historial de actividad y consultas IA realizadas por este usuario.
     */
    public function aiHistory(User $user): JsonResponse
    {
        $userId = 'user_' . $user->id;
        $sessions = $this->fastApiClient->listChatSessions($userId);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name ?? 'Sin rol',
            ],
            'sessions' => $sessions,
        ]);
    }
}
