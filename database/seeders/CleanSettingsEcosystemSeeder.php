<?php

namespace Database\Seeders;

use App\Models\Ecosystem;
use App\Models\EcosystemModule;
use App\Models\EcosystemInterface;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class CleanSettingsEcosystemSeeder extends Seeder
{
    public function run(): void
    {
        $settings = Ecosystem::where('key', 'settings')->first();
        if (!$settings) return;

        // Limpiar módulos previos para dejar las 3 opciones exactas
        $moduleIds = $settings->modules()->pluck('id');
        EcosystemInterface::whereIn('module_id', $moduleIds)->delete();
        $settings->modules()->delete();

        // MÓDULO 1: Personalización Visual (Una sola vista y opción en el sidebar)
        $mVisual = $settings->modules()->create([
            'key' => 'settings_visual',
            'name' => 'Personalización Visual',
            'icon' => 'fa-palette',
            'order_num' => 1,
            'is_active' => true,
        ]);

        $mVisual->interfaces()->create([
            'name' => 'Apariencia & Paletas',
            'icon' => 'fa-swatchbook',
            'route_or_action' => 'settings.index',
            'action_type' => 'route',
            'badge_label' => 'Temas',
            'order_num' => 1,
            'is_active' => true,
        ]);

        // MÓDULO 2: Gestión de Usuarios
        $mUsers = $settings->modules()->create([
            'key' => 'settings_users',
            'name' => 'Gestión de Usuarios',
            'icon' => 'fa-users',
            'order_num' => 2,
            'is_active' => true,
        ]);

        $mUsers->interfaces()->create([
            'name' => 'Directorio & Auditoría',
            'icon' => 'fa-id-card',
            'route_or_action' => 'settings.users.index',
            'action_type' => 'route',
            'badge_label' => 'Colaboradores',
            'order_num' => 1,
            'is_active' => true,
        ]);

        // MÓDULO 3: Roles & Permisos (RBAC)
        $mRoles = $settings->modules()->create([
            'key' => 'settings_roles',
            'name' => 'Control de Acceso (RBAC)',
            'icon' => 'fa-shield-halved',
            'order_num' => 3,
            'is_active' => true,
        ]);

        $mRoles->interfaces()->create([
            'name' => 'Roles & Permisos',
            'icon' => 'fa-user-lock',
            'route_or_action' => 'settings.roles.index',
            'action_type' => 'route',
            'badge_label' => 'Gobernanza',
            'order_num' => 1,
            'is_active' => true,
        ]);

        // Registrar catálogo ampliado de permisos de vistas y opciones
        $perms = [
            // Vistas principales
            ['name' => 'Acceso a Vista Chat', 'slug' => 'views.chat', 'module' => 'Vistas del Sistema', 'description' => 'Permite acceder a la consola conversacional IA'],
            ['name' => 'Acceso a Vista Conexiones', 'slug' => 'views.connections', 'module' => 'Vistas del Sistema', 'description' => 'Permite ingresar al gestor de bases de datos'],
            ['name' => 'Acceso a Vista Configuración', 'slug' => 'views.settings', 'module' => 'Vistas del Sistema', 'description' => 'Permite ingresar al ecosistema de configuración'],

            // Acciones de Chat
            ['name' => 'Interactuar con Chat IA', 'slug' => 'chat.use', 'module' => 'Chat & Analítica', 'description' => 'Permite enviar prompts y hacer preguntas a la IA'],
            ['name' => 'Entrenar Base de Conocimiento', 'slug' => 'chat.train', 'module' => 'Chat & Analítica', 'description' => 'Permite agregar reglas semánticas y entrenamiento'],
            ['name' => 'Exportar Resultados a Excel', 'slug' => 'chat.export', 'module' => 'Chat & Analítica', 'description' => 'Permite descargar reportes analíticos generados'],

            // Acciones de Conexiones
            ['name' => 'Gestionar Conexiones BD', 'slug' => 'connections.manage', 'module' => 'Bases de Datos', 'description' => 'Permite agregar, editar o probar credenciales de BD'],
            ['name' => 'Indexar & Vectorizar Esquemas', 'slug' => 'connections.vectorize', 'module' => 'Bases de Datos', 'description' => 'Permite sincronizar tablas con la base vectorial Qdrant'],

            // Acciones de Configuración
            ['name' => 'Modificar Tema y Paletas', 'slug' => 'settings.theme', 'module' => 'Administración', 'description' => 'Permite alterar colores y tipografías globales'],
            ['name' => 'Administrar Usuarios', 'slug' => 'users.manage', 'module' => 'Administración', 'description' => 'Permite registrar, inactivar y asignar roles a colaboradores'],
            ['name' => 'Administrar Roles & Permisos', 'slug' => 'roles.manage', 'module' => 'Administración', 'description' => 'Permite crear roles y ajustar la matriz de privilegios'],
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
