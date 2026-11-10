<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\FastApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    protected FastApiClient $fastApiClient;

    public function __construct(FastApiClient $fastApiClient)
    {
        $this->fastApiClient = $fastApiClient;
    }

    /**
     * Muestra la vista de administración de roles, permisos detallados y asignación a usuarios.
     */
    public function index(Request $request): View
    {
        $roles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::all()->groupBy('module');
        $isOnline = $this->fastApiClient->checkHealth();

        $selectedRole = null;
        if ($request->has('role_id')) {
            $selectedRole = Role::with('permissions', 'users')->find($request->get('role_id'));
        }

        return view('settings.roles', [
            'roles' => $roles,
            'groupedPermissions' => $permissions,
            'selectedRole' => $selectedRole,
            'isOnline' => $isOnline,
        ]);
    }

    /**
     * Crea un nuevo rol.
     */
    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('settings.roles.index')->with('success', "Rol '{$role->name}' creado exitosamente.");
    }

    /**
     * Actualiza información básica de un rol existente.
     */
    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('settings.roles.index')->with('success', "Rol '{$role->name}' actualizado correctamente.");
    }

    /**
     * Alterna el estado activo / inactivo de un rol.
     */
    public function toggleActiveRole(Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            return redirect()->route('settings.roles.index')->with('error', 'El rol Super Administrador no puede ser inactivado.');
        }

        $role->is_active = !$role->is_active;
        $role->save();

        $statusStr = $role->is_active ? 'activado' : 'inactivado';
        return redirect()->route('settings.roles.index')->with('success', "El rol '{$role->name}' ha sido {$statusStr}.");
    }

    /**
     * Actualiza los permisos asociados a un rol específico.
     */
    public function syncRolePermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('settings.roles.index', ['role_id' => $role->id])->with('success', "Permisos guardados para el rol '{$role->name}'.");
    }

    /**
     * Elimina un rol existente (impidiendo borrar super-admin).
     */
    public function destroyRole(Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            return redirect()->route('settings.roles.index')->with('error', 'El rol Super Administrador no puede ser eliminado.');
        }

        User::where('role_id', $role->id)->update(['role_id' => null]);
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('settings.roles.index')->with('success', "Rol '{$role->name}' eliminado.");
    }

    /**
     * Retorna los usuarios asociados a este rol en formato JSON para visualización rápida.
     */
    public function roleUsers(Role $role): JsonResponse
    {
        $users = $role->users()->select('id', 'name', 'email', 'is_active', 'created_at')->get();

        return response()->json([
            'success' => true,
            'role' => $role->name,
            'users' => $users,
        ]);
    }
}
