<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Módulo Chat & RAG
            ['name' => 'Usar Asistente IA', 'slug' => 'chat.use', 'module' => 'Chat & RAG', 'description' => 'Permite interactuar con el asistente analítico y generar consultas'],
            ['name' => 'Entrenar Base de Conocimiento', 'slug' => 'chat.train', 'module' => 'Chat & RAG', 'description' => 'Permite registrar y eliminar reglas de entrenamiento corporativo'],
            ['name' => 'Gestionar Conocimiento y Candidatos', 'slug' => 'manage-knowledge', 'module' => 'Chat & RAG', 'description' => 'Permite administrar, aprobar, revertir y promover candidatos de conocimiento global'],
            ['name' => 'Exportar Datasets', 'slug' => 'chat.export', 'module' => 'Chat & RAG', 'description' => 'Permite descargar resultados analíticos en formato Excel'],

            // Módulo Conexiones & Vectorización
            ['name' => 'Ver Conexiones', 'slug' => 'connections.view', 'module' => 'Conexiones BD', 'description' => 'Permite consultar el listado de conexiones a bases de datos'],
            ['name' => 'Gestionar Conexiones', 'slug' => 'connections.manage', 'module' => 'Conexiones BD', 'description' => 'Permite crear, probar, activar o eliminar conexiones de datos'],
            ['name' => 'Ejecutar Vectorización', 'slug' => 'connections.vectorize', 'module' => 'Conexiones BD', 'description' => 'Permite indexar esquemas y tablas en la base vectorial Qdrant'],

            // Módulo Configuración y Gobernanza
            ['name' => 'Ver Configuración', 'slug' => 'settings.view', 'module' => 'Configuración', 'description' => 'Permite visualizar las preferencias de interfaz y personalización'],
            ['name' => 'Modificar Preferencias', 'slug' => 'settings.modify', 'module' => 'Configuración', 'description' => 'Permite cambiar colores corporativos, tipografía y tema visual'],
            ['name' => 'Gestionar Roles & Permisos', 'slug' => 'roles.manage', 'module' => 'Administración', 'description' => 'Permite crear roles, asignar permisos y auditar accesos de usuarios'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // Roles Principales
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Administrador',
            'description' => 'Acceso total y sin restricciones a todos los ecosistemas, herramientas y gobernanza del sistema.',
            'is_active' => true,
        ]);

        $dataAnalyst = Role::firstOrCreate(['slug' => 'data-analyst'], [
            'name' => 'Analista de Datos',
            'description' => 'Acceso al Asistente IA, exportación analítica y visualización de conexiones activas.',
            'is_active' => true,
        ]);

        $ragOperator = Role::firstOrCreate(['slug' => 'rag-operator'], [
            'name' => 'Operador RAG',
            'description' => 'Acceso a la gestión de reglas de conocimiento y vectorización de esquemas.',
            'is_active' => true,
        ]);

        // Asignar permisos al Analista de Datos
        $analystPerms = Permission::whereIn('slug', ['chat.use', 'chat.export', 'connections.view', 'settings.view'])->pluck('id');
        $dataAnalyst->permissions()->sync($analystPerms);

        // Asignar permisos al Operador RAG
        $ragPerms = Permission::whereIn('slug', ['chat.use', 'chat.train', 'connections.view', 'connections.manage', 'connections.vectorize', 'settings.view'])->pluck('id');
        $ragOperator->permissions()->sync($ragPerms);

        // Si existen usuarios sin rol, asignar el rol de Super Administrador al primer usuario
        $firstUser = User::first();
        if ($firstUser && !$firstUser->role_id) {
            $firstUser->update(['role_id' => $superAdmin->id]);
        }
    }
}
